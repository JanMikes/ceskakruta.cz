<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Order;

use CeskaKruta\Web\Exceptions\BoxxiRequestFailed;
use CeskaKruta\Web\Exceptions\BoxxiShipmentNumberMissing;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Order\CancelBoxxiShipment;
use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * `POST /v1/shipments/{shipmentNumber}/cancel`. A failed request throws and the order stays sent.
 */
#[AsMessageHandler]
readonly final class CancelBoxxiShipmentHandler
{
    public const REASON = 'Zrušeno v administraci ceskakruta.cz';

    public function __construct(
        private GetAdminOrders $getAdminOrders,
        private AdminRecordRepository $repository,
        private HttpClientInterface $coolbalikClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws RecordNotFound
     * @throws BoxxiShipmentNumberMissing
     * @throws BoxxiRequestFailed
     */
    public function __invoke(CancelBoxxiShipment $message): void
    {
        $record = $this->getAdminOrders->oneById($message->orderId);

        if ($record->boxxiSyncedAt === null || $record->boxxiShipmentNumber === null) {
            throw new BoxxiShipmentNumberMissing();
        }

        try {
            $this->coolbalikClient->request('POST', '/v1/shipments/' . rawurlencode($record->boxxiShipmentNumber) . '/cancel', [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['Reason' => self::REASON], JSON_THROW_ON_ERROR),
            ])->getContent();
        } catch (ExceptionInterface $exception) {
            $this->logger->error('Boxxi cancel of shipment {number} failed: {message}', ['number' => $record->boxxiShipmentNumber, 'message' => $exception->getMessage(), 'exception' => $exception]);

            throw new BoxxiRequestFailed('Boxxi request failed.', previous: $exception);
        }

        $this->repository->update('order', $message->orderId, [
            'boxxi_sync_dt' => null,
            'boxxi_shipment_number' => null,
        ], $message->adminId);
    }
}
