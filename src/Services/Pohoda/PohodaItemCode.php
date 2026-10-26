<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Pohoda;

use CeskaKruta\Web\Value\Admin\AdminOrderItem;

/**
 * Pohoda item code + text, ported 1:1 from old `libs/services/OrderService.php` (`getOrderItemCode`, `getOrderItemName`):
 * whole turkey items (`product_tp_id` 1 Krůta / 2 Krocan × `product_id` 1, 2, 41, 42) have fixed codes 600001…600048
 * and fixed names, every other item uses `product.code` / `product.name`.
 */
final class PohodaItemCode
{
    /**
     * `ord:code` + `typ:stockItem/typ:ids`; NULL when the product has no code (rendered as empty element).
     */
    public function code(AdminOrderItem $item): null|string
    {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * `ord:text`
     */
    public function name(AdminOrderItem $item): string
    {
        throw new \LogicException('Not implemented yet');
    }
}
