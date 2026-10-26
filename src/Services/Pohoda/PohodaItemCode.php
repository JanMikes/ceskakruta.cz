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
    /** product_tp_id => product_id => [code, name] */
    private const TURKEYS = [
        1 => [
            1 => ['600001', 'Krůta celá s droby'],
            2 => ['600002', 'Krůta - půlka bez drobů'],
            41 => ['600040', 'Porcovaná Krůta - celý kus'],
            42 => ['600041', 'Porcovaná Krůta - půlka'],
        ],
        2 => [
            1 => ['600045', 'Krocan celý s droby'],
            2 => ['600046', 'Krocan - půlka bez drobů'],
            41 => ['600047', 'Porcovaný Krocan - celý kus'],
            42 => ['600048', 'Porcovaný Krocan - půlka'],
        ],
    ];

    /**
     * `ord:code` + `typ:stockItem/typ:ids`; NULL when the product has no code (rendered as empty element).
     */
    public function code(AdminOrderItem $item): null|string
    {
        return self::turkey($item)[0] ?? $item->productCode;
    }

    /**
     * `ord:text`
     */
    public function name(AdminOrderItem $item): string
    {
        return self::turkey($item)[1] ?? $item->productName;
    }

    /**
     * @return null|array{string, string}
     */
    private static function turkey(AdminOrderItem $item): null|array
    {
        if ($item->productTypeId === null) {
            return null;
        }

        return self::TURKEYS[$item->productTypeId][$item->productId] ?? null;
    }
}
