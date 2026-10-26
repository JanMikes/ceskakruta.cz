<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Value\Admin\PendingNewsletter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

readonly final class GetPendingNewsletters
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Oldest queue rows first (PLAN §6, Phase 1 review 5).
     *
     * @return list<PendingNewsletter>
     */
    public function next(int $limit): array
    {
        $rows = $this->connection
            ->executeQuery(<<<'SQL'
SELECT an.id, an.actuality_id, an.email, a.name, a.text
FROM actuality_newsletter an
JOIN actuality a ON a.id = an.actuality_id
WHERE an.is_sent_flag = 0 AND an.active_flag = 1 AND an.del_flag = 0
ORDER BY an.id
LIMIT :limit
SQL, ['limit' => $limit], ['limit' => ParameterType::INTEGER])
            ->fetchAllAssociative();

        return array_map(static function (array $row): PendingNewsletter {
            /** @var array{id: int, actuality_id: int, email: null|string, name: null|string, text: null|string} $row */

            return new PendingNewsletter(
                id: $row['id'],
                actualityId: $row['actuality_id'],
                email: $row['email'] ?? '',
                name: $row['name'] ?? '',
                text: $row['text'] ?? '',
            );
        }, $rows);
    }
}
