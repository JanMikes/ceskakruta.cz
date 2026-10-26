<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Newsletter queue `actuality_newsletter` (PLAN §2.4, §2.5), unique on (email, actuality_id) → INSERT IGNORE.
 */
readonly final class ActualityNewsletterRepository
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Every active subscriber, done on actuality create (old ManageActualityFormFactory).
     */
    public function enqueueActiveSubscribers(int $actualityId, int $adminId): int
    {
        return (int) $this->connection->executeStatement(
            <<<'SQL'
INSERT IGNORE INTO actuality_newsletter (actuality_id, email, ins_dt, ins_process, ins_user_id)
SELECT DISTINCT :actuality_id, email, :now, 'admin', :admin_id
FROM newsletter
WHERE del_flag = 0 AND active_flag = 1 AND email IS NOT NULL
SQL,
            [
                'actuality_id' => $actualityId,
                'now' => date('Y-m-d H:i:s'),
                'admin_id' => (string) $adminId,
            ],
        );
    }

    /**
     * Old cron "fill missing": subscribers older than the latest actuality (highest id) not queued for it yet.
     * A deleted / inactive latest actuality is not filled (PLAN §6, Phase 1 review 6).
     */
    public function fillMissingForLatestActuality(int $limit): int
    {
        $latest = $this->connection
            ->executeQuery('SELECT id, ins_dt, active_flag, del_flag FROM actuality ORDER BY id DESC LIMIT 1')
            ->fetchAssociative();

        /** @var false|array{id: int, ins_dt: string, active_flag: int|string, del_flag: int|string} $latest */
        if ($latest === false || (int) $latest['active_flag'] !== 1 || (int) $latest['del_flag'] !== 0) {
            return 0;
        }

        return (int) $this->connection->executeStatement(
            <<<'SQL'
INSERT IGNORE INTO actuality_newsletter (actuality_id, email, ins_dt, ins_process)
SELECT DISTINCT :actuality_id, n.email, :now, 'cron'
FROM newsletter n
WHERE n.del_flag = 0
    AND n.active_flag = 1
    AND n.email IS NOT NULL
    AND n.ins_dt < :actuality_ins_dt
    AND NOT EXISTS (
        SELECT 1 FROM actuality_newsletter an WHERE an.actuality_id = :actuality_id AND an.email = n.email
    )
LIMIT :limit
SQL,
            [
                'actuality_id' => $latest['id'],
                'actuality_ins_dt' => $latest['ins_dt'],
                'now' => date('Y-m-d H:i:s'),
                'limit' => $limit,
            ],
            ['limit' => ParameterType::INTEGER],
        );
    }

    public function markProcessed(int $id, string $process): void
    {
        $this->connection->update('actuality_newsletter', [
            'is_sent_flag' => 1,
            'upd_process' => $process,
        ], ['id' => $id]);
    }
}
