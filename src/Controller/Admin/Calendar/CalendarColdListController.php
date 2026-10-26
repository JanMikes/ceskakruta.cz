<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Calendar;

use CeskaKruta\Web\Query\Admin\CountInactiveRecords;
use CeskaKruta\Web\Query\Admin\GetAdminCalendarCold;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarColdListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminCalendarCold $getAdminCalendarCold,
        readonly private CountInactiveRecords $countInactiveRecords,
    ) {
    }

    #[Route(path: '/admin/chlazene-kruty', name: 'admin_calendar_cold')]
    public function __invoke(): Response
    {
        return $this->render('admin/calendar/cold_list.html.twig', [
            'rows' => $this->getAdminCalendarCold->all(),
            'inactive_count' => $this->countInactiveRecords->count(AdminTable::CalendarCold),
        ]);
    }
}
