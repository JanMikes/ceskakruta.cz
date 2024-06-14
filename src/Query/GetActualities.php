<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Value\Actuality;
use Doctrine\DBAL\Connection;

final class GetActualities
{
    /** @var array<Actuality>|null  */
    private null|array $actualities = null;

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    /**
     * @return array<Actuality>
     */
    public function all(): array
    {
        if ($this->actualities === null) {
            $rows = $this->connection
                ->executeQuery('SELECT * FROM actuality WHERE active_flag = 1 AND del_flag = 0 ORDER BY date DESC')
                ->fetchAllAssociative();

            $actualities = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     id: int,
                 *     date: string,
                 *     name: string,
                 *     text: null|string,
                 *     text_full: null|string,
                 * } $row
                 */

                $id = $row['id'];

                $date = \DateTimeImmutable::createFromFormat('Y-m-d', $row['date']);
                assert($date instanceof \DateTimeImmutable);

                $actualities[$id] = new Actuality(
                    id: $id,
                    date: $date,
                    title: $row['name'],
                    text: $row['text'] ?? '',
                    textFull: $row['text_full'] ?? '',
                );
            }

            $this->actualities = $actualities;
        }

        return $this->actualities;
    }

    public function oneById(int|string $actualityId): Actuality
    {
        return $this->all()[$actualityId] ?? throw new \Exception('Actuality not found');
    }
}
