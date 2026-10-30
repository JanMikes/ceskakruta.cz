<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * Orders of one day (expedition or customer day, see `DateBasis`), summed from `OrderGroup`s.
 */
final class DayStats
{
    public int $ordersCount = 0;
    public float $revenue = 0.0;
    public int $withNoteCount = 0;
    public int $pohodaMissingCount = 0;
    public int $boxxiMissingCount = 0;

    /** @var array<string, int> orders count by `SalesChannel` value */
    public array $byChannel = [];

    public function __construct(
        readonly public \DateTimeImmutable $date,
    ) {
    }

    public function add(OrderGroup $group): void
    {
        $this->ordersCount += $group->ordersCount;
        $this->revenue += $group->revenue;
        $this->withNoteCount += $group->withNoteCount;
        $this->pohodaMissingCount += $group->pohodaMissingCount;
        $this->boxxiMissingCount += $group->boxxiMissingCount;
        $this->byChannel[$group->channel->value] = ($this->byChannel[$group->channel->value] ?? 0) + $group->ordersCount;
    }

    /**
     * @return list<array{channel: SalesChannel, count: int}>
     */
    public function channels(): array
    {
        $channels = [];

        foreach (SalesChannel::cases() as $channel) {
            if (($this->byChannel[$channel->value] ?? 0) > 0) {
                $channels[] = ['channel' => $channel, 'count' => $this->byChannel[$channel->value]];
            }
        }

        return $channels;
    }
}
