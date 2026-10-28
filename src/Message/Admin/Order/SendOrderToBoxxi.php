<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Order;

use CeskaKruta\Web\FormData\Admin\BoxxiFormData;
use CeskaKruta\Web\Value\Admin\BoxxiShipmentData;

/**
 * Saves the Boxxi form values on the order and creates the shipment, or updates it when the order was already sent
 * (`boxxi_sync_dt`) and its `boxxi_shipment_number` is known. The handler returns a `BoxxiSyncResult`.
 */
readonly final class SendOrderToBoxxi
{
    public function __construct(
        public int $orderId,
        public int $adminId,
        public BoxxiShipmentData $shipment,
    ) {
    }

    public static function fromFormData(int $orderId, int $adminId, BoxxiFormData $formData): self
    {
        assert($formData->date !== null && $formData->coolBalikPrice !== null && $formData->coolBalikBoxCount !== null && $formData->weight !== null);

        return new self(
            orderId: $orderId,
            adminId: $adminId,
            shipment: new BoxxiShipmentData($formData->date, $formData->coolBalikPrice, $formData->coolBalikBoxCount, $formData->weight),
        );
    }

    /**
     * @return array<string, float|int|string>
     */
    public function columns(): array
    {
        return [
            'date' => $this->shipment->deliveryDate->format('Y-m-d'),
            'cool_balik_price' => $this->shipment->price,
            'cool_balik_box_count' => $this->shipment->boxCount,
            'cool_balik_weight' => $this->shipment->weight,
            'boxxi_weight' => $this->shipment->weight,
        ];
    }
}
