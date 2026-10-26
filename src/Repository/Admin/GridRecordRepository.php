<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Repository\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use CeskaKruta\Web\Value\Admin\Grid\MoveDirection;
use Doctrine\DBAL\Connection;

/**
 * Generic writes behind the grid actions. Table and column names come only from the `AdminTable` whitelist.
 * Deleted rows (`del_flag = 1`) are invisible, like in the old `Entity::findBy()`.
 */
readonly final class GridRecordRepository
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     * @throws RecordNotFound
     */
    public function get(AdminTable $table, int $id): array
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT * FROM `%s` WHERE id = :id AND del_flag = 0', $table->value),
            ['id' => $id],
        );

        if ($row === false) {
            throw new RecordNotFound();
        }

        return $row;
    }

    public function setActive(AdminTable $table, int $id, bool $active, int $adminId): void
    {
        $this->update($table, $id, ['active_flag' => (int) $active], $adminId);
    }

    public function softDelete(AdminTable $table, int $id, int $adminId): void
    {
        $this->update($table, $id, ['del_flag' => 1], $adminId);
    }

    public function activateAll(AdminTable $table, null|int $scope, int $adminId): void
    {
        $query = $this->connection->createQueryBuilder()
            ->update('`' . $table->value . '`')
            ->set('active_flag', '1')
            ->set('upd_process', ':process')
            ->set('upd_user_id', ':userId')
            ->where('active_flag = 0')
            ->andWhere('del_flag = 0')
            ->setParameter('process', 'admin')
            ->setParameter('userId', (string) $adminId);

        $scopeColumn = $table->scopeColumn();
        if ($scopeColumn !== null) {
            $query->andWhere('`' . $scopeColumn . '` = :scope')
                ->setParameter('scope', $scope);
        }

        $query->executeStatement();
    }

    /**
     * Port of the old `Entity::changeRowOrder()`: lists are sorted `order DESC`, an empty order is initialised to id*10,
     * then the row swaps `order` with the nearest row in the direction (equal values: ±1). No neighbour = no change.
     *
     * @throws RecordNotFound
     */
    public function move(AdminTable $table, int $id, MoveDirection $direction, int $adminId): void
    {
        $row = $this->get($table, $id);
        $order = is_numeric($row['order'] ?? null) ? (int) $row['order'] : 0;

        if ($order === 0) {
            $order = $id * 10;
            $this->update($table, $id, ['order' => $order], $adminId);
        }

        $sql = sprintf(
            'SELECT id, `order` FROM `%s` WHERE id != :id AND del_flag = 0 AND `order` %s :order',
            $table->value,
            $direction === MoveDirection::Up ? '>=' : '<=',
        );
        $params = ['id' => $id, 'order' => $order];

        $scopeColumn = $table->scopeColumn();
        if ($scopeColumn !== null) {
            $sql .= sprintf(' AND `%s` <=> :scope', $scopeColumn);
            $params['scope'] = $row[$scopeColumn];
        }

        $sql .= $direction === MoveDirection::Up ? ' ORDER BY `order` ASC, id DESC' : ' ORDER BY `order` DESC, id ASC';

        /** @var false|array{id: int, order: int} $neighbour */
        $neighbour = $this->connection->fetchAssociative($sql . ' LIMIT 1', $params);

        if ($neighbour === false) {
            return;
        }

        $newOrder = (int) $neighbour['order'];
        if ($newOrder === $order) {
            $newOrder += $direction === MoveDirection::Up ? 1 : -1;
        }

        $this->update($table, (int) $neighbour['id'], ['order' => $order], $adminId);
        $this->update($table, $id, ['order' => $newOrder], $adminId);
    }

    /**
     * @param array<string, int> $data
     */
    private function update(AdminTable $table, int $id, array $data, int $adminId): void
    {
        $quoted = [];
        foreach ($data as $column => $value) {
            $quoted['`' . $column . '`'] = $value;
        }

        $this->connection->update(
            '`' . $table->value . '`',
            $quoted + [
                'upd_process' => 'admin',
                'upd_user_id' => (string) $adminId,
            ],
            ['id' => $id],
        );
    }
}
