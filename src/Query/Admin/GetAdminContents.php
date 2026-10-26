<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminContent;
use Doctrine\DBAL\Connection;

readonly final class GetAdminContents
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return list<AdminContent>
     */
    public function all(): array
    {
        $rows = $this->connection
            ->executeQuery('SELECT id, code, name, text FROM content WHERE del_flag = 0 ORDER BY `order` DESC')
            ->fetchAllAssociative();

        return array_map(self::createContent(...), $rows);
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminContent
    {
        $row = $this->connection
            ->executeQuery('SELECT id, code, name, text FROM content WHERE id = :id AND del_flag = 0', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        return self::createContent($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function createContent(array $row): AdminContent
    {
        /**
         * @var array{
         *     id: int,
         *     code: null|string,
         *     name: null|string,
         *     text: null|string,
         * } $row
         */

        return new AdminContent(
            id: $row['id'],
            code: $row['code'],
            name: $row['name'],
            text: $row['text'],
        );
    }
}
