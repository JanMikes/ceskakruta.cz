<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Admin\Order;

use CeskaKruta\Web\Value\Admin\AdminOrderDetailItem;
use CeskaKruta\Web\Value\Admin\AdminOrderItem;
use CeskaKruta\Web\Value\Admin\AdminOrderRecord;
use CeskaKruta\Web\Value\Admin\Order\OrderChange;

/**
 * Differences between an order before and after an admin edit, in Czech for the customer email. Pure.
 * Items are matched by `order_item.id` (kept for edited items, new id for added ones).
 */
final class OrderChangeList
{
    public const REMOVED = 'odebráno';
    public const NONE = '–';

    /**
     * @param list<AdminOrderDetailItem> $beforeItems
     * @param list<AdminOrderDetailItem> $afterItems
     * @return list<OrderChange>
     */
    public static function between(AdminOrderRecord $before, array $beforeItems, AdminOrderRecord $after, array $afterItems, bool $showPrices): array
    {
        $changes = [];
        $field = static function (string $label, string $old, string $new) use (&$changes): void {
            if ($old !== $new) {
                $changes[] = new OrderChange($label, $old !== '' ? $old : self::NONE, $new !== '' ? $new : self::NONE);
            }
        };

        $field('Místo', $before->order->place->name, $after->order->place->name);
        $field('Datum', $before->order->date->format('d.m.Y'), $after->order->date->format('d.m.Y'));
        $field('Jméno a příjmení', $before->order->name, $after->order->name);
        $field('Telefon', $before->order->phone, $after->order->phone);
        $field('E-mail', $before->order->email, $after->order->email);
        $field('Adresa doručení', self::address($before), self::address($after));
        $field('Platba', $before->order->payByCard ? 'kartou' : 'hotově', $after->order->payByCard ? 'kartou' : 'hotově');
        $field('Poznámka', trim($before->order->note), trim($after->order->note));

        $old = [];
        foreach ($beforeItems as $item) {
            $old[$item->item->id] = $item;
        }

        foreach ($afterItems as $item) {
            $previous = $old[$item->item->id] ?? null;
            unset($old[$item->item->id]);

            $now = self::item($item, $showPrices);

            if ($previous === null) {
                $changes[] = new OrderChange($item->item->productName, self::NONE, $now);
            } elseif (($was = self::item($previous, $showPrices)) !== $now || $previous->item->productId !== $item->item->productId) {
                $changes[] = new OrderChange($item->item->productName, ($previous->item->productId !== $item->item->productId ? $previous->item->productName . ': ' : '') . $was, $now);
            }
        }

        foreach ($old as $item) {
            $changes[] = new OrderChange($item->item->productName, self::item($item, $showPrices), self::REMOVED);
        }

        if ($showPrices) {
            $field('Cena celkem', self::money($before->priceTotal), self::money($after->priceTotal));
        }

        return $changes;
    }

    public static function item(AdminOrderDetailItem $detail, bool $showPrices): string
    {
        $item = $detail->item;
        $parts = [self::number($item->amount) . ' ' . $item->productUnit];

        if ($item->weightFrom !== null && $item->weightTo !== null) {
            $parts[] = self::weight($item);
        }

        if ($item->isSliced) {
            $parts[] = 'nakrájet';
        }

        if ($item->isPacked) {
            $parts[] = 'vakuovat';
        }

        if ($item->note !== null && trim($item->note) !== '') {
            $parts[] = 'poznámka: ' . trim($item->note);
        }

        $text = implode(', ', $parts);

        return $showPrices ? $text . ' = ' . self::money((float) $detail->priceTotal) : $text;
    }

    public static function weight(AdminOrderItem $item): string
    {
        return self::number((float) $item->weightFrom) . '–' . self::number((float) $item->weightTo) . ' kg/ks';
    }

    private static function address(AdminOrderRecord $record): string
    {
        $order = $record->order;
        $cityLine = trim(($order->deliveryCity ?? '') . ' ' . ($order->deliveryPostalCode ?? ''));

        return implode(', ', array_filter([trim($order->deliveryStreet ?? ''), $cityLine], static fn (string $part): bool => $part !== ''));
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    }

    private static function money(float $value): string
    {
        return number_format($value, 0, '', ' ') . ' Kč';
    }
}
