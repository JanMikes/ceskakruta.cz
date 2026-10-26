<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Pohoda;

use CeskaKruta\Web\Value\Admin\AdminOrder;
use CeskaKruta\Web\Value\Admin\AdminOrderItem;
use Twig\Environment;

/**
 * Pohoda (mServer) XML documents, ported 1:1 from old `libs/services/Pohoda/*.xml.latte` + `Pohoda::sendOrderRequest`
 * (characterization: golden files in tests/fixtures/pohoda/, rendered by the old code).
 * Templates: templates/admin/pohoda/*.xml.twig (escaped once, the old templates double-escaped the customer name).
 */
readonly final class PohodaOrderXmlFactory
{
    public function __construct(
        private Environment $twig,
        private PohodaDates $dates,
        private PohodaItemCode $itemCode,
    ) {
    }

    /**
     * Order documents to send, in sending order, keyed by order number: `WEB{id}` (items without whole turkeys, or all items
     * when not split) and `WEB{id}K` (whole turkeys of a split CoolBalík order, see PohodaDates::splitsWholeTurkeys()).
     * A document without items is not created.
     *
     * @param list<AdminOrderItem> $items in `order_item.id` order
     * @param null|string $addressId Pohoda address book id (`typ:id` in `ord:partnerIdentity`), only for delivery places
     * @return array<string, string>
     */
    public function orderDocuments(AdminOrder $order, array $items, null|string $addressId): array
    {
        $dateTo = $this->dates->dateTo($order->date, $order->place);
        $mainItems = $items;
        $turkeyItems = [];

        if ($this->dates->splitsWholeTurkeys($dateTo, $order->place)) {
            $mainItems = array_values(array_filter($items, static fn (AdminOrderItem $item): bool => $item->weightFrom === null));
            $turkeyItems = array_values(array_filter($items, static fn (AdminOrderItem $item): bool => $item->weightFrom !== null));
        }

        $documents = [];

        if ($mainItems !== []) {
            $documents['WEB' . $order->id] = $this->orderXml($order, $mainItems, $addressId, 'WEB' . $order->id, $dateTo, false);
        }

        if ($turkeyItems !== []) {
            $documents['WEB' . $order->id . 'K'] = $this->orderXml($order, $turkeyItems, $addressId, 'WEB' . $order->id . 'K', $dateTo, true);
        }

        return $documents;
    }

    /**
     * `search-address.xml.latte`: address book filter by name, city, street, zip.
     */
    public function searchAddressXml(AdminOrder $order): string
    {
        return $this->twig->render('admin/pohoda/search-address.xml.twig', ['order' => $order]);
    }

    /**
     * `address.xml.latte`: creates the address book entry when the search found nothing.
     */
    public function createAddressXml(AdminOrder $order): string
    {
        return $this->twig->render('admin/pohoda/address.xml.twig', ['order' => $order]);
    }

    /**
     * @param list<AdminOrderItem> $items
     */
    private function orderXml(
        AdminOrder $order,
        array $items,
        null|string $addressId,
        string $orderNumber,
        \DateTimeImmutable $dateTo,
        bool $wholeTurkeysOrder,
    ): string {
        return $this->twig->render('admin/pohoda/order.xml.twig', [
            'order' => $order,
            'order_number' => $orderNumber,
            'address_id' => $addressId,
            'date_to' => $dateTo,
            'date_from' => $this->dates->dateFrom($dateTo, $order->place, $wholeTurkeysOrder),
            'items' => array_map(fn (AdminOrderItem $item): array => [
                'item' => $item,
                'code' => $this->itemCode->code($item),
                'name' => $this->itemCode->name($item),
            ], $items),
        ]);
    }
}
