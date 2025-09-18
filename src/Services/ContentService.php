<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use Doctrine\DBAL\Connection;

final class ContentService
{
    /**
     * @var null|array<string, string>
     */
    private null|array $rows = null;

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    public function text(string $key): null|string
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        if ($this->rows === null) {
            $rows = $this->connection
                ->executeQuery('SELECT * FROM content WHERE active_flag = 1 AND del_flag = 0')
                ->fetchAllAssociative();

            $this->rows = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     code: string,
                 *     text: string,
                 * } $row
                 */

                $this->rows[$row['code']] = $row['text'];
            }
        }

        return $this->rows;
    }
}
