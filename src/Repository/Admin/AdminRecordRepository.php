<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Repository\Admin;

use Doctrine\DBAL\Connection;

/**
 * Inserts / updates of the admin forms on the legacy tables with the audit columns (docs/admin/PLAN.md §3.1, §4.6).
 * Callers pass literal table names and columns only, values may come from forms.
 */
readonly final class AdminRecordRepository
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param array<string, null|scalar> $data column => value, booleans are stored as 0/1
     * @param bool $orderable the old app set `order = id * 10` after the insert, so new rows go to the bottom of `order DESC` lists
     */
    public function insert(string $table, array $data, int $adminId, bool $orderable = false): int
    {
        $this->connection->insert('`' . $table . '`', self::quote($data) + [
            'ins_dt' => date('Y-m-d H:i:s'),
            'ins_process' => 'admin',
            'ins_user_id' => (string) $adminId,
        ]);

        $id = (int) $this->connection->lastInsertId();

        if ($orderable) {
            $this->connection->update('`' . $table . '`', ['`order`' => $id * 10], ['id' => $id]);
        }

        return $id;
    }

    /**
     * @param array<string, null|scalar> $data
     */
    public function update(string $table, int $id, array $data, int $adminId): void
    {
        $this->connection->update('`' . $table . '`', self::quote($data) + [
            'upd_process' => 'admin',
            'upd_user_id' => (string) $adminId,
        ], ['id' => $id]);
    }

    /**
     * @param array<string, null|scalar> $data
     * @return array<string, null|scalar>
     */
    private static function quote(array $data): array
    {
        $quoted = [];

        foreach ($data as $column => $value) {
            $quoted['`' . $column . '`'] = is_bool($value) ? (int) $value : $value;
        }

        return $quoted;
    }
}
