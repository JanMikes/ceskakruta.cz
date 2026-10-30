<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin;

use CeskaKruta\Web\Components\Admin\DashboardOperations;
use CeskaKruta\Web\Query\Admin\GetDashboardTrends;
use CeskaKruta\Web\Services\Admin\Dashboard\DashboardDays;
use CeskaKruta\Web\Services\Admin\Dashboard\RecurringOrdersWatch;
use CeskaKruta\Web\Value\Admin\Dashboard\DateBasis;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Quick overview: what needs attention, received orders and the next days by expedition, details on the other pages.
 */
final class AdminDashboardController extends AbstractController
{
    public function __construct(
        readonly private GetDashboardTrends $getDashboardTrends,
        readonly private DashboardDays $dashboardDays,
        readonly private RecurringOrdersWatch $recurringOrdersWatch,
        readonly private ClockInterface $clock,
    ) {
    }

    #[Route(path: '/admin', name: 'admin_dashboard')]
    public function __invoke(): Response
    {
        $now = $this->clock->now();
        $today = $now->setTime(0, 0);

        // Current period so far vs the same part of the comparison period – created orders (`ins_dt`)
        $kpis = $this->getDashboardTrends->periodTotals([
            'today' => [$today, $now],
            'today_previous' => [$today->modify('-7 days'), $now->modify('-7 days')],
            'month' => [$today->modify('first day of this month'), $now],
            'month_previous' => [$today->modify('first day of this month')->modify('-1 year'), $now->modify('-1 year')],
            'year' => [$today->modify('first day of january this year'), $now],
            'year_previous' => [$today->modify('first day of january this year')->modify('-1 year'), $now->modify('-1 year')],
        ]);

        $nextDay = DashboardDays::nextDay($today);
        $days = $this->dashboardDays->days(DateBasis::Expedition, $today, $today->modify(sprintf('+%d days', DashboardOperations::STRIP_DAYS - 1)));

        return $this->render('admin/dashboard.html.twig', [
            'kpis' => $kpis,
            'sync' => $this->dashboardDays->syncAttention($today),
            'expected_orders' => $this->recurringOrdersWatch->expectedOrders($now, $today, $today->modify(sprintf('+%d days', AdminRecurringOrdersController::DAYS - 1))),
            'days' => array_values($days),
            'today_stats' => $days[$today->format('Y-m-d')],
            'next_day_stats' => $days[$nextDay->format('Y-m-d')],
            'next_day_title' => DashboardDays::nextDayTitle($today),
        ]);
    }
}
