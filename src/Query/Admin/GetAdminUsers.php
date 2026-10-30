<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminUser;
use CeskaKruta\Web\Value\Admin\AdminUserFilter;
use CeskaKruta\Web\Value\Admin\AdminUserListItem;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

readonly final class GetAdminUsers
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Newest registrations first, orders count = non-deleted orders, banned count = active & non-deleted bans.
     *
     * @return list<AdminUserListItem>
     */
    public function filtered(AdminUserFilter $filter, int $offset, int $limit): array
    {
        $rows = $this->filteredQuery($filter)
            ->select(
                'u.id', 'u.ins_dt', 'u.email', 'u.name', 'u.allow_wholesale', 'u.is_admin',
                'COALESCE(orders.orders_count, 0) AS orders_count',
                'COALESCE(bans.banned_count, 0) AS banned_count',
            )
            ->leftJoin('u', '(SELECT user_id, COUNT(*) AS orders_count FROM `order` WHERE del_flag = 0 AND user_id IS NOT NULL GROUP BY user_id)', 'orders', 'orders.user_id = u.id')
            ->leftJoin('u', '(SELECT user_id, COUNT(*) AS banned_count FROM user_banned_product WHERE active_flag = 1 AND del_flag = 0 GROUP BY user_id)', 'bans', 'bans.user_id = u.id')
            ->orderBy('u.ins_dt', 'DESC')
            ->addOrderBy('u.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(static function (array $row): AdminUserListItem {
            /**
             * @var array{
             *     id: int,
             *     ins_dt: string,
             *     email: null|string,
             *     name: null|string,
             *     allow_wholesale: int,
             *     is_admin: int,
             *     orders_count: int|string,
             *     banned_count: int|string,
             * } $row
             */

            return new AdminUserListItem(
                id: $row['id'],
                registeredAt: new DateTimeImmutable($row['ins_dt']),
                email: $row['email'],
                name: $row['name'],
                ordersCount: (int) $row['orders_count'],
                bannedProductsCount: (int) $row['banned_count'],
                allowWholesale: (bool) $row['allow_wholesale'],
                isAdmin: (bool) $row['is_admin'],
            );
        }, $rows);
    }

    public function count(AdminUserFilter $filter): int
    {
        $count = $this->filteredQuery($filter)
            ->select('COUNT(*)')
            ->executeQuery()
            ->fetchOne();
        assert(is_numeric($count));

        return (int) $count;
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminUser
    {
        $row = $this->connection
            ->executeQuery('SELECT * FROM user WHERE id = :id AND del_flag = 0', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        /**
         * @var array{
         *     id: int,
         *     email: null|string,
         *     name: null|string,
         *     phone: null|string,
         *     preferred_place_id: null|int,
         *     delivery_street: null|string,
         *     delivery_city: null|string,
         *     delivery_zip: null|string,
         *     company_invoicing: int,
         *     company_name: null|string,
         *     company_id: null|string,
         *     company_vat_id: null|string,
         *     invoicing_street: null|string,
         *     invoicing_city: null|string,
         *     invoicing_zip: null|string,
         *     allow_wholesale: int,
         *     is_ceskakruta: int,
         *     is_admin: int,
         * } $row
         */

        return new AdminUser(
            id: $row['id'],
            email: $row['email'],
            name: $row['name'],
            phone: $row['phone'],
            preferredPlaceId: $row['preferred_place_id'],
            deliveryStreet: $row['delivery_street'],
            deliveryCity: $row['delivery_city'],
            deliveryZip: $row['delivery_zip'],
            companyInvoicing: (bool) $row['company_invoicing'],
            companyName: $row['company_name'],
            companyId: $row['company_id'],
            companyVatId: $row['company_vat_id'],
            invoicingStreet: $row['invoicing_street'],
            invoicingCity: $row['invoicing_city'],
            invoicingZip: $row['invoicing_zip'],
            allowWholesale: (bool) $row['allow_wholesale'],
            isCeskakruta: (bool) $row['is_ceskakruta'],
            isAdmin: (bool) $row['is_admin'],
        );
    }

    private function filteredQuery(AdminUserFilter $filter): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder()
            ->from('user', 'u')
            ->where('u.del_flag = 0');

        if ($filter->search !== null) {
            $query->andWhere('u.email LIKE :search OR u.name LIKE :search OR u.phone LIKE :search OR u.company_name LIKE :search')
                ->setParameter('search', '%' . $filter->search . '%');
        }

        if ($filter->wholesale !== null) {
            $query->andWhere('u.allow_wholesale = :wholesale')->setParameter('wholesale', (int) $filter->wholesale);
        }

        return $query;
    }
}
