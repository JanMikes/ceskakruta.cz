<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Order;

use CeskaKruta\Web\Exceptions\BoxxiRequestFailed;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Order\SendOrderToBoxxi;
use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use CeskaKruta\Web\Services\Boxxi\BoxxiShipmentFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Old `CoolBalikFormFactory::process` + `Boxxi::sendOrderRequest`. A failed request (transport error, non-2xx) throws,
 * so the whole update is rolled back and the order stays unsynced.
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
    public function __invoke(SendOrderToBoxxi $message): bool
    {
        $record = $this->getAdminOrders->oneById($message->orderId);

        $this->repository->update('order', $message->orderId, $message->columns(), $message->adminId);

        if ($record->boxxiSyncedAt !== null) {
            return false;
        }

        try {
            $this->coolbalikClient->request('POST', '/v1/shipments', [
                'headers' => ['Content-Type' => 'application/json'],
                // Plain json_encode like the old app (HttpClient's `json` option would send 22.0 instead of 22)
                'body' => json_encode($this->shipmentFactory->payload($record->order, $message->shipment), JSON_THROW_ON_ERROR),
            ])->getContent();
        } catch (ExceptionInterface $exception) {
            $this->logger->error('Boxxi request failed: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);

            throw new BoxxiRequestFailed('Boxxi request failed.', previous: $exception);
        }

        $this->repository->update('order', $message->orderId, [
            'boxxi_sync_dt' => date('Y-m-d H:i:s'),
        ], $message->adminId);

        return true;
    }
}
