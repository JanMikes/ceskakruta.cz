<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Repository;

use CeskaKruta\Web\Entity\RecurringOrderSkip;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

readonly final class RecurringOrderSkipRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function save(RecurringOrderSkip $skip): void
    {
        $this->entityManager->persist($skip);
    }

    public function remove(RecurringOrderSkip $skip): void
    {
        $this->entityManager->remove($skip);
    }

    public function findActiveSkip(int $userId, int $dayOfWeek): null|RecurringOrderSkip
    {
        $now = $this->clock->now();

        /** @var null|RecurringOrderSkip $skip */
        $skip = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(RecurringOrderSkip::class, 's')
            ->where('s.userId = :userId')
            ->andWhere('s.dayOfWeek = :dayOfWeek')
            ->andWhere('s.skipUntil > :now')
            ->setParameter('userId', $userId)
            ->setParameter('dayOfWeek', $dayOfWeek)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();

        return $skip;
    }

    /**
     * @return array<int, RecurringOrderSkip>
     */
    public function getActiveSkipsForUser(int $userId): array
    {
        $now = $this->clock->now();
        $skipsById = [];

        /** @var list<RecurringOrderSkip> $skips */
        $skips = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(RecurringOrderSkip::class, 's')
            ->where('s.userId = :userId')
            ->andWhere('s.skipUntil > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        foreach ($skips as $skip) {
            $skipsById[$skip->dayOfWeek] = $skip;
        }

        return $skipsById;
    }

    /**
     * @return array<string, list<int>>
     */
    public function getActiveSkipsByUserAndDay(): array
    {
        $now = $this->clock->now();

        /** @var list<array{userId: int, dayOfWeek: int}> $skips */
        $skips = $this->entityManager->createQueryBuilder()
            ->select('s.userId', 's.dayOfWeek')
            ->from(RecurringOrderSkip::class, 's')
            ->where('s.skipUntil > :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $skipsByUserAndDay = [];
        foreach ($skips as $skip) {
            $key = $skip['userId'] . '_' . $skip['dayOfWeek'];
            $skipsByUserAndDay[$key] = [$skip['userId'], $skip['dayOfWeek']];
        }

        return $skipsByUserAndDay;
    }
}