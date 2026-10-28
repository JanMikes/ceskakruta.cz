<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Order;

use CeskaKruta\Web\Exceptions\BoxxiRequestFailed;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Order\SendOrderToBoxxi;
use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use CeskaKruta\Web\Services\Boxxi\BoxxiShipmentFactory;
use CeskaKruta\Web\Value\Admin\BoxxiSyncResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Old `CoolBalikFormFactory::process` + `Boxxi::sendOrderRequest`. A failed request (transport error, non-2xx) throws,
 * so the whole update is rolled back and the order stays as it was.
 *
 * Not sent yet → create the shipment and store its number. Sent with a known number → update the shipment.
 * Sent before the number was stored → only the form values are saved (old behaviour).
 */
#[AsMessageHandler]
readonly final class SendOrderToBoxxiHandler
{
    public function __construct(
        private GetAdminOrders $getAdminOrders,
        private AdminRecordRepository $repository,
        private BoxxiShipmentFactory $shipmentFactory,
        private HttpClientInterface $coolbalikClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws RecordNotFound
     * @throws BoxxiRequestFailed
     */
    public function __invoke(SendOrderToBoxxi $message): BoxxiSyncResult
    {
        $record = $this->getAdminOrders->oneById($message->orderId);

        $this->repository->update('order', $message->orderId, $message->columns(), $message->adminId);

        if ($record->boxxiSyncedAt !== null) {
            if ($record->boxxiShipmentNumber === null) {
                return BoxxiSyncResult::NotSent;
            }

            $this->request('/v1/shipments/' . rawurlencode($record->boxxiShipmentNumber) . '/update', $this->shipmentFactory->updatePayload($message->shipment));

            return BoxxiSyncResult::Updated;
        }

        $response = $this->request('/v1/shipments', $this->shipmentFactory->payload($record->order, $message->shipment));

        $this->repository->update('order', $message->orderId, [
            'boxxi_sync_dt' => date('Y-m-d H:i:s'),
            'boxxi_shipment_number' => $this->shipmentNumber($message->orderId, $response),
        ], $message->adminId);

        return BoxxiSyncResult::Created;
    }

    /**
     * @param array<string, mixed> $payload
     * @throws BoxxiRequestFailed
     */
    private function request(string $path, array $payload): string
    {
        try {
            return $this->coolbalikClient->request('POST', $path, [
                'headers' => ['Content-Type' => 'application/json'],
                // Plain json_encode like the old app (HttpClient's `json` option would send 22.0 instead of 22)
                'body' => json_encode($payload, JSON_THROW_ON_ERROR),
            ])->getContent();
        } catch (ExceptionInterface $exception) {
            $this->logger->error('Boxxi request {path} failed: {message}', ['path' => $path, 'message' => $exception->getMessage(), 'exception' => $exception]);

            throw new BoxxiRequestFailed('Boxxi request failed.', previous: $exception);
        }
    }

    /**
     * The shipment already exists in Boxxi at this point, so an unexpected response must not roll back the sync
     * (a retry would create a duplicate) – it is only logged and the number stays NULL.
     */
    private function shipmentNumber(int $orderId, string $response): null|string
    {
        $data = json_decode($response, true);
        $number = is_array($data) ? ($data['ShipmentNumber'] ?? null) : null;

        if (!is_string($number) || $number === '') {
            $this->logger->warning('Boxxi response for order {orderId} has no ShipmentNumber: {response}', ['orderId' => $orderId, 'response' => $response]);

            return null;
        }

        return $number;
    }
}
