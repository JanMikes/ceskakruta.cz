<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Boxxi;

use CeskaKruta\Web\Value\Admin\AdminOrder;
use CeskaKruta\Web\Value\Admin\BoxxiShipmentData;

/**
 * `POST /v1/shipments` payload, ported 1:1 from old `libs/services/Boxxi.php` (fixed sender address, COD = price,
 * DeliverAt = delivery date 12:00:00, EveningDelivery, …). Key order and value types are part of the contract
 * (golden files in tests/fixtures/boxxi/). Pure, no dependencies.
 */
final class BoxxiShipmentFactory
{
    /**
     * @return array<string, mixed> JSON-encodable payload
     */
    public function payload(AdminOrder $order, BoxxiShipmentData $shipment): array
    {
        throw new \LogicException('Not implemented yet');
    }
}
