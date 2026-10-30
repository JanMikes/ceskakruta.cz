<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin;

use CeskaKruta\Web\Services\Admin\Dashboard\RecurringOrdersWatch;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Recurring orders the cron should create in the next days, missing ones have to be handled by hand.
 */
final class AdminRecurringOrdersController extends AbstractController
{
    public const DAYS = 7;

    public function __construct(
        readonly private RecurringOrdersWatch $recurringOrdersWatch,
        readonly private ClockInterface $clock,
    ) {
    }

    #[Route(path: '/admin/pravidelne-objednavky', name: 'admin_recurring_orders')]
    public function __invoke(): Response
    {
        $now = $this->clock->now();
        $today = $now->setTime(0, 0);

        return $this->render('admin/recurring_orders.html.twig', [
            'expected_orders' => $this->recurringOrdersWatch->expectedOrders($now, $today, $today->modify(sprintf('+%d days', self::DAYS - 1))),
        ]);
    }
}
