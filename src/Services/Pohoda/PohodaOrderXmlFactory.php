<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Pohoda;

use CeskaKruta\Web\Value\Admin\AdminOrder;
use CeskaKruta\Web\Value\Admin\AdminOrderItem;

/**
 * Pohoda (mServer) XML documents, ported 1:1 from old `libs/services/Pohoda/*.xml.latte` + `Pohoda::sendOrderRequest`
 * (characterization: golden files in tests/fixtures/pohoda/, rendered by the old code).
 * Constructor dependencies (e.g. Twig, PohodaDates, PohodaItemCode) are up to the implementation, the service is fetched
 * from the container in tests.
 */
final class PohodaOrderXmlFactory
{
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
        throw new \LogicException('Not implemented yet');
    }

    /**
     * `search-address.xml.latte`: address book filter by name, city, street, zip.
     */
    public function searchAddressXml(AdminOrder $order): string
    {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * `address.xml.latte`: creates the address book entry when the search found nothing.
     */
    public function createAddressXml(AdminOrder $order): string
    {
        throw new \LogicException('Not implemented yet');
    }
}
