<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Components\Admin;

use CeskaKruta\Web\Services\Admin\Dashboard\DashboardDays;
use CeskaKruta\Web\Value\Admin\Dashboard\DateBasis;
use CeskaKruta\Web\Value\Admin\Dashboard\DayOrder;
use CeskaKruta\Web\Value\Admin\Dashboard\DayStats;
use CeskaKruta\Web\Value\Admin\Dashboard\PrepItem;
use Psr\Clock\ClockInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Dashboard operations: today + next working day, 7-day strip, month calendar and the selected day's prep list and
 * orders, all by expedition or customer day. One orders query for all days per render (+ items and orders of the day).
 */
#[AsLiveComponent(name: 'Admin:DashboardOperations', template: 'components/Admin/DashboardOperations.html.twig', route: 'admin_live_component')]
final class DashboardOperations
{
    use DefaultActionTrait;

    public const STRIP_DAYS = 7;

    #[LiveProp(writable: true, url: true)]
    public DateBasis $basis = DateBasis::Expedition;

    /** Selected day `Y-m-d`, empty = today */
    #[LiveProp(url: true)]
    public string $day = '';

    /** Calendar month `Y-m`, empty = month of the selected day */
    #[LiveProp(url: true)]
    public string $month = '';

    /** @var null|array<string, DayStats> */
    private null|array $days = null;

    public function __construct(
        readonly private DashboardDays $dashboardDays,
        readonly private ClockInterface $clock,
    ) {
    }

    #[LiveAction]
    public function selectDay(#[LiveArg] string $day): void
    {
        $date = self::parse('Y-m-d', $day);

        if ($date !== null) {
            $this->day = $date->format('Y-m-d');
            $this->month = $date->format('Y-m');
        }
    }

    #[LiveAction]
    public function showMonth(#[LiveArg] string $month): void
    {
        if (self::parse('Y-m', $month) !== null) {
            $this->month = $month;
        }
    }

    public function today(): \DateTimeImmutable
    {
        return $this->clock->now()->setTime(0, 0);
    }

    public function nextDay(): \DateTimeImmutable
    {
        return DashboardDays::nextDay($this->today());
    }

    public function nextDayTitle(): string
    {
        return DashboardDays::nextDayTitle($this->today());
    }

    /**
     * @return list<DateBasis>
     */
    public function bases(): array
    {
        return DateBasis::cases();
    }

    public function selectedDay(): \DateTimeImmutable
    {
        return self::parse('Y-m-d', $this->day) ?? $this->today();
    }

    public function monthStart(): \DateTimeImmutable
    {
        return self::parse('Y-m', $this->month)?->modify('first day of this month') ?? $this->selectedDay()->modify('first day of this month');
    }

    public function dayStats(\DateTimeImmutable $day): DayStats
    {
        return $this->days()[$day->format('Y-m-d')] ?? new DayStats($day);
    }

    /**
     * @return list<DayStats>
     */
    public function strip(): array
    {
        $strip = [];

        for ($i = 0; $i < self::STRIP_DAYS; $i++) {
            $strip[] = $this->dayStats($this->today()->modify(sprintf('+%d days', $i)));
        }

        return $strip;
    }

    /**
     * Calendar weeks Monday–Sunday covering the month, days outside the month are null.
     *
     * @return list<list<null|DayStats>>
     */
    public function calendarWeeks(): array
    {
        $monthStart = $this->monthStart();
        $monthEnd = $monthStart->modify('last day of this month');
        $weeks = [];
        $week = array_fill(0, (int) $monthStart->format('N') - 1, null);

        for ($day = $monthStart; $day <= $monthEnd; $day = $day->modify('+1 day')) {
            $week[] = $this->dayStats($day);

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        if ($week !== []) {
            $weeks[] = array_values(array_pad($week, 7, null));
        }

        return $weeks;
    }

    /**
     * Heat level 0–4 of a calendar day relative to the busiest day of the month.
     */
    public function heatLevel(DayStats $day): int
    {
        $max = 0;
        $monthKey = $this->monthStart()->format('Y-m');

        foreach ($this->days() as $key => $stats) {
            if (str_starts_with($key, $monthKey)) {
                $max = max($max, $stats->ordersCount);
            }
        }

        if ($day->ordersCount === 0 || $max === 0) {
            return 0;
        }

        return (int) ceil(4 * $day->ordersCount / $max);
    }

    /**
     * @return list<PrepItem>
     */
    public function prepItems(): array
    {
        return $this->dashboardDays->prepItems($this->basis, $this->selectedDay());
    }

    /**
     * @return list<DayOrder>
     */
    public function dayOrders(): array
    {
        return $this->dashboardDays->orders($this->basis, $this->selectedDay());
    }

    /**
     * @return array<string, DayStats>
     */
    private function days(): array
    {
        if ($this->days === null) {
            $monthStart = $this->monthStart();
            $from = min($monthStart, $this->today(), $this->selectedDay());
            $to = max(
                $monthStart->modify('last day of this month'),
                $this->today()->modify(sprintf('+%d days', self::STRIP_DAYS - 1)),
                $this->selectedDay(),
            );

            $this->days = $this->dashboardDays->days($this->basis, $from, $to);
        }

        return $this->days;
    }

    private static function parse(string $format, string $value): null|\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);

        return $date !== false && $date->format($format) === $value ? $date : null;
    }
}
