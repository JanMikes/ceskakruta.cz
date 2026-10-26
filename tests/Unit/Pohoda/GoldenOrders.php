<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Pohoda;

use CeskaKruta\Web\Value\Admin\AdminOrder;
use CeskaKruta\Web\Value\Admin\AdminOrderItem;
use CeskaKruta\Web\Value\Admin\AdminOrderPlace;

/**
 * Value objects of the fixture orders 5901–5905 (tests/database/fixtures/50-orders.sql), i.e. the exact input the golden
 * files in tests/fixtures/{pohoda,boxxi}/ were rendered from with the OLD app code (tests/fixtures/pohoda/_renderer/).
 */
final class GoldenOrders
{
    public const FIXTURES_DIR = __DIR__ . '/../../fixtures';

    public static function file(string $relativePath): string
    {
        $content = file_get_contents(self::FIXTURES_DIR . '/' . $relativePath);
        assert(is_string($content), 'Missing fixture ' . $relativePath);

        return $content;
    }

    public static function shopPlace(): AdminOrderPlace
    {
        return new AdminOrderPlace(1, 'Rychvald - Prodejna', isCoolBalik: false, forceDeliveryAddress: false);
    }

    public static function deliveryPlace(): AdminOrderPlace
    {
        return new AdminOrderPlace(7, 'Rozvoz domů Přerov', isCoolBalik: false, forceDeliveryAddress: true);
    }

    public static function coolBalikPlace(): AdminOrderPlace
    {
        return new AdminOrderPlace(21, 'Rozvoz CoolBalík', isCoolBalik: true, forceDeliveryAddress: true);
    }

    /** Shop other than place 1, without delivery (fixture place 30) */
    public static function otherShopPlace(): AdminOrderPlace
    {
        return new AdminOrderPlace(30, 'Skrytá prodejna', isCoolBalik: false, forceDeliveryAddress: false);
    }

    public static function order(int $id): AdminOrder
    {
        return match ($id) {
            5901 => new AdminOrder(5901, self::dt('2024-06-10 08:15:00'), self::dt('2024-06-14'), 'Jana Nováková', 'jana.novakova@example.test', '+420600100200',
                null, null, null, 'Vyzvednu odpoledne', false, null, self::shopPlace()),
            5902 => new AdminOrder(5902, self::dt('2024-06-12 19:40:00'), self::dt('2024-06-19'), 'Petr Svoboda', 'petr.svoboda@example.test', '+420600300400',
                'Hlavní 12', 'Přerov', '75002', '', false, null, self::deliveryPlace()),
            5903 => new AdminOrder(5903, self::dt('2024-06-14 07:05:00'), self::dt('2024-06-21'), 'Marie Dvořáková', 'marie.dvorakova@example.test', '+420600500600',
                'Školní 7', 'Olomouc', '77900', 'Doručit po 16. hodině', true, null, self::coolBalikPlace()),
            5904 => new AdminOrder(5904, self::dt('2024-06-11 12:00:00'), self::dt('2024-06-18'), 'Eva Malá', 'eva.mala@example.test', '+420600700800',
                null, null, null, 'Děkuji', false, 10, self::shopPlace()),
            5905 => new AdminOrder(5905, self::dt('2024-06-18 21:30:00'), self::dt('2024-06-25'), 'Tomáš Černý', 'tomas.cerny@example.test', '+420600900100',
                'Nádražní 3', 'Ostrava', '70200', 'Zvonit u "Černých" & nechat u dveří', true, null, self::coolBalikPlace()),
            default => throw new \InvalidArgumentException('No golden order ' . $id),
        };
    }

    /**
     * Items in `order_item.id` order.
     *
     * @return list<AdminOrderItem>
     */
    public static function items(int $orderId): array
    {
        return match ($orderId) {
            5901 => [self::meat(5901, 2), self::ham(5902, 3)],
            5902 => [self::meat(5903, 4), self::turkey(5904, 2, 1, 'Půlka krůty bez drobů', 127, 4, 5)],
            5903 => [self::ham(5905, 1), self::meat(5906, 2), self::turkey(5907, 1, 1, 'Krůta celá s droby', 123, 6, 7)],
            5904 => [self::meat(5908, 2)],
            5905 => [self::turkey(5909, 41, 2, 'Porcovaná krůta - celá s droby', 129, 10, 12), self::ham(5910, 2), self::meat(5911, 10)],
            default => throw new \InvalidArgumentException('No golden order ' . $orderId),
        };
    }

    /** Product 100 "Krůtí prsa", per kg, code 100100 */
    public static function meat(int $id, float $amount): AdminOrderItem
    {
        return new AdminOrderItem($id, 100, null, 'Krůtí prsa', '100100', 'kg', 1.0, $amount, 289, null, null);
    }

    /** Product 101 "Krůtí šunka", per piece (0.3 kg), code 200100 */
    public static function ham(int $id, float $amount): AdminOrderItem
    {
        return new AdminOrderItem($id, 101, null, 'Krůtí šunka', '200100', 'ks', 0.3, $amount, 89, null, null);
    }

    /** Whole turkey product (ids 1/2/41/42, no product code, unit ks) of type 1 Krůta / 2 Krocan */
    public static function turkey(int $id, int $productId, int $productTypeId, string $productName, int $price, float $weightFrom, float $weightTo, float $amount = 1): AdminOrderItem
    {
        return new AdminOrderItem($id, $productId, $productTypeId, $productName, null, 'ks', 1.0, $amount, $price, $weightFrom, $weightTo);
    }

    public static function dt(string $value): \DateTimeImmutable
    {
        return new \DateTimeImmutable($value);
    }
}
