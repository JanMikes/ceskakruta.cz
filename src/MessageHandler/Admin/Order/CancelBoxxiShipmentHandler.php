<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Order;

use CeskaKruta\Web\Exceptions\BoxxiRequestFailed;
use CeskaKruta\Web\Exceptions\BoxxiShipmentNumberMissing;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Order\CancelBoxxiShipment;
use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use CeskaKruta\Web\Services\Boxxi\BoxxiShipments;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * `POST /v1/shipments/{shipmentNumber}/cancel`. A failed request throws and the order stays sent.
 */
#[AsMessageHandler]
readonly final class CancelBoxxiShipmentHandler
{
    public const REASON = BoxxiShipments::CANCEL_REASON;

    public function __construct(
        private GetAdminOrders $getAdminOrders,
        private AdminRecordRepository $repository,
        private BoxxiShipments $shipments,
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

        $this->shipments->cancel($record->boxxiShipmentNumber);

        $this->repository->update('order', $message->orderId, [
            'boxxi_sync_dt' => null,
            'boxxi_shipment_number' => null,
        ], $message->adminId);
    }
}
