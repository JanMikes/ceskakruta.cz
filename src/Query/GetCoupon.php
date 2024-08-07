<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Value\Coupon;
use Doctrine\DBAL\Connection;

final class GetCoupon
{
    private null|false|Coupon $coupon = null;

    /** @var array<Coupon>|null  */
    private null|array $coupons = null;

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    public function oneById(int $id): null|Coupon
    {
        if ($this->coupons === null) {
            $rows = $this->connection
                ->executeQuery('SELECT * FROM coupon WHERE del_flag = 0')
                ->fetchAllAssociative();

            $coupons = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     id: int,
                 *     code: string,
                 *     usage_limit: null|int,
                 *     until_date: null|string,
                 *     delivery_until_date: null|string,
                 *     percent_value: null|int,
                 * } $row
                 */

                $id = $row['id'];

                $untilDate = $row['until_date'];
                if ($untilDate !== null) {
                    $untilDate = \DateTimeImmutable::createFromFormat('Y-m-d', $untilDate);
                    assert($untilDate instanceof \DateTimeImmutable);
                }

                $deliveryUntilDate = $row['delivery_until_date'];
                if ($deliveryUntilDate !== null) {
                    $deliveryUntilDate = \DateTimeImmutable::createFromFormat('Y-m-d', $deliveryUntilDate);
                    assert($deliveryUntilDate instanceof \DateTimeImmutable);
                }

                $coupons[$id] = new Coupon(
                    id: $row['id'],
                    code: $row['code'],
                    usageLimit: $row['usage_limit'],
                    untilDate: $untilDate,
                    deliveryUntilDate: $deliveryUntilDate,
                    percentValue: $row['percent_value'],
                );
            }

            $this->coupons = $coupons;
        }

        return $this->coupons[$id] ?? null;
    }

    public function oneByCode(string $code): null|Coupon
    {
        $code = str_replace(
            ['ě', 'š', 'č', 'ř', 'ž', 'ý', 'á', 'í', 'é', 'ů', 'ú'],
            ['e', 's', 'c', 'r', 'z', 'y', 'a', 'i', 'e', 'u', 'u'],
            strtolower($code)
        );

        if ($this->coupon === null) {
            $row = $this->connection
                ->executeQuery('SELECT * FROM coupon WHERE active_flag = 1 AND del_flag = 0 AND LOWER(code) = LOWER(:code)', [
                    'code' => $code,
                ])
                ->fetchAssociative();

            /**
             * @var array{
             *     id: int,
             *     code: string,
             *     usage_limit: null|int,
             *     until_date: null|string,
             *     delivery_until_date: null|string,
             *     percent_value: null|int,
             * }|false $row
             */

            if ($row === false) {
                $this->coupon = false;

                return null;
            }

            $untilDate = $row['until_date'];
            if ($untilDate !== null) {
                $untilDate = \DateTimeImmutable::createFromFormat('Y-m-d', $untilDate);
                assert($untilDate instanceof \DateTimeImmutable);
            }

            $deliveryUntilDate = $row['delivery_until_date'];
            if ($deliveryUntilDate !== null) {
                $deliveryUntilDate = \DateTimeImmutable::createFromFormat('Y-m-d', $deliveryUntilDate);
                assert($deliveryUntilDate instanceof \DateTimeImmutable);
            }

            $coupon = new Coupon(
                id: $row['id'],
                code: $row['code'],
                usageLimit: $row['usage_limit'],
                untilDate: $untilDate,
                deliveryUntilDate: $deliveryUntilDate,
                percentValue: $row['percent_value'],
            );

            $this->coupon = $coupon;
        }

        return $this->coupon === false ? null : $this->coupon;
    }
}
