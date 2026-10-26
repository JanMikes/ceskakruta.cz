<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Calendar;

use CeskaKruta\Web\Query\Admin\CountInactiveRecords;
use CeskaKruta\Web\Query\Admin\GetAdminCalendarDead;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarDeadListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminCalendarDead $getAdminCalendarDead,
        readonly private CountInactiveRecords $countInactiveRecords,
    ) {
    }

    #[Route(path: '/admin/rozkrmena-krutata', name: 'admin_calendar_dead')]
    public function __invoke(): Response
    {
        return $this->render('admin/calendar/dead_list.html.twig', [
            'rows' => $this->getAdminCalendarDead->all(),
            'inactive_count' => $this->countInactiveRecords->count(AdminTable::CalendarDead),
        ]);
    }
}
