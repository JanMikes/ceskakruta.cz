<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin;

use CeskaKruta\Web\Query\Admin\GetDashboardTrends;
use CeskaKruta\Web\Value\Admin\Dashboard\PeriodTotals;
use CeskaKruta\Web\Value\Admin\Dashboard\SalesChannel;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Analytics: monthly revenue by channel, order counts year over year and best selling products.
 */
final class AdminStatisticsController extends AbstractController
{
    private const CHART_MONTHS = 13;

    public function __construct(
        readonly private GetDashboardTrends $getDashboardTrends,
        readonly private ChartBuilderInterface $chartBuilder,
        readonly private ClockInterface $clock,
    ) {
    }

    #[Route(path: '/admin/statistiky', name: 'admin_statistics')]
    public function __invoke(): Response
    {
        $today = $this->clock->now()->setTime(0, 0);

        $chartFrom = $today->modify('first day of this month')->modify(sprintf('-%d months', self::CHART_MONTHS - 1));
        $chartTo = $today->modify('first day of next month');
        // The order count chart compares with the year before, so one query covers both years
        $monthly = $this->getDashboardTrends->monthlyByChannel($chartFrom->modify('-1 year'), $chartTo);
        $months = [];

        for ($month = $chartFrom; $month < $chartTo; $month = $month->modify('+1 month')) {
            $months[] = $month;
        }

        return $this->render('admin/statistics.html.twig', [
            'months' => $months,
            'monthly' => $monthly,
            'channels' => SalesChannel::cases(),
            'revenue_chart' => $this->revenueChart($months, $monthly),
            'orders_chart' => $this->ordersChart($months, $monthly),
        ]);
    }

    /**
     * Revenue per month stacked by sales channel.
     *
     * @param list<\DateTimeImmutable> $months
     * @param array<string, array<string, PeriodTotals>> $monthly
     */
    private function revenueChart(array $months, array $monthly): Chart
    {
        $datasets = [];

        foreach (SalesChannel::cases() as $channel) {
            $datasets[] = [
                'label' => $channel->label(),
                'data' => array_map(static fn (\DateTimeImmutable $month): int => (int) round($monthly[$month->format('Y-m')][$channel->value]->revenue ?? 0), $months),
                'backgroundColor' => $channel->color(),
                'borderColor' => '#ffffff',
                'borderWidth' => ['top' => 2],
                'borderRadius' => 4,
                'borderSkipped' => 'bottom',
                'maxBarThickness' => 40,
            ];
        }

        return $this->chart(Chart::TYPE_BAR, self::monthLabels($months), $datasets, stacked: true, unit: 'Kč');
    }

    /**
     * Created orders per month vs the same month a year before (one axis, two series).
     *
     * @param list<\DateTimeImmutable> $months
     * @param array<string, array<string, PeriodTotals>> $monthly
     */
    private function ordersChart(array $months, array $monthly): Chart
    {
        $count = static fn (\DateTimeImmutable $month): int => array_sum(array_map(
            static fn (PeriodTotals $totals): int => $totals->ordersCount,
            $monthly[$month->format('Y-m')] ?? [],
        ));

        return $this->chart(Chart::TYPE_LINE, self::monthLabels($months), [
            [
                'label' => 'Posledních 13 měsíců',
                'data' => array_map($count, $months),
                'borderColor' => '#2a78b8',
                'backgroundColor' => '#2a78b8',
                'borderWidth' => 2,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
                'pointBorderColor' => '#ffffff',
                'pointBorderWidth' => 2,
                'tension' => 0.25,
            ],
            [
                'label' => 'O rok dříve',
                'data' => array_map(static fn (\DateTimeImmutable $month): int => $count($month->modify('-1 year')), $months),
                'borderColor' => '#9aa1ad',
                'backgroundColor' => '#9aa1ad',
                'borderWidth' => 2,
                'borderDash' => [6, 4],
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
                'pointBorderColor' => '#ffffff',
                'pointBorderWidth' => 2,
                'tension' => 0.25,
            ],
        ], stacked: false, unit: null);
    }

    /**
     * @param list<string> $labels
     * @param list<array<string, mixed>> $datasets
     */
    private function chart(string $type, array $labels, array $datasets, bool $stacked, null|string $unit): Chart
    {
        $chart = $this->chartBuilder->createChart($type);
        $chart->setData(['labels' => $labels, 'datasets' => $datasets]);
        $chart->setOptions([
            'locale' => 'cs-CZ',
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'boxHeight' => 8, 'color' => '#52514e']],
                'tooltip' => ['backgroundColor' => '#1d2230', 'padding' => 10, 'boxPadding' => 4, 'usePointStyle' => true],
            ],
            'scales' => [
                'x' => ['stacked' => $stacked, 'grid' => ['display' => false], 'ticks' => ['color' => '#6b7280']],
                'y' => [
                    'stacked' => $stacked,
                    'beginAtZero' => true,
                    'border' => ['display' => false],
                    'grid' => ['color' => '#eef0f3'],
                    'ticks' => ['color' => '#6b7280', 'precision' => 0],
                    'title' => ['display' => $unit !== null, 'text' => (string) $unit, 'color' => '#6b7280'],
                ],
            ],
        ]);

        return $chart;
    }

    /**
     * @param list<\DateTimeImmutable> $months
     * @return list<string>
     */
    private static function monthLabels(array $months): array
    {
        return array_map(static fn (\DateTimeImmutable $month): string => $month->format('n/Y'), $months);
    }
}
