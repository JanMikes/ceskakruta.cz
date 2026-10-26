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
        return [
            'Note' => $order->note,
            'DeliverAt' => $shipment->deliveryDate->setTime(12, 0)->format('Y-m-d H:i:s'),
            'PreparedForPickup' => false,
            'CodAmount' => (int) $shipment->price,
            'ClientOrderNumber' => (string) $order->id,
            'Currency' => 'CZK',
            'PackageCount' => $shipment->boxCount,
            'EveningDelivery' => true,
            'TotalWeight' => $shipment->weight,
            'Temperature' => 'Normal',
            'AddressFrom' => [
                'Country' => 'CZ',
                'ContactName' => 'Česká Krůta',
                'PhoneNumber' => '+420 596 546 672',
                'Email' => 'farma.rychvald@volny.cz',
                'CompanyName' => 'B+K FARMA Rychvald s.r.o.',
                'Street' => 'Rychvald 1215',
                'StreetLine1' => null,
                'StreetLine2' => null,
                'City' => 'Rychvald',
                'PostalCode' => '73532',
                'Note' => '',
                'IsAddressDc' => true,
            ],
            'AddressTo' => [
                'Country' => 'CZ',
                'ContactName' => $order->name,
                'PhoneNumber' => $order->phone,
                'Email' => $order->email,
                'CompanyName' => '',
                'Street' => $order->deliveryStreet,
                'StreetLine1' => null,
                'StreetLine2' => null,
                'City' => $order->deliveryCity,
                'PostalCode' => $order->deliveryPostalCode,
                'Note' => '',
                'IsAddressDc' => false,
            ],
        ];
    }
}
