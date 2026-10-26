<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Calendar;

use CeskaKruta\Web\Query\Admin\CountInactiveRecords;
use CeskaKruta\Web\Query\Admin\GetAdminCalendarAlive;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarAliveListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminCalendarAlive $getAdminCalendarAlive,
        readonly private CountInactiveRecords $countInactiveRecords,
    ) {
    }

    #[Route(path: '/admin/zive-kruty', name: 'admin_calendar_alive')]
    public function __invoke(): Response
    {
        return $this->render('admin/calendar/alive_list.html.twig', [
            'rows' => $this->getAdminCalendarAlive->all(),
            'inactive_count' => $this->countInactiveRecords->count(AdminTable::CalendarAlive),
        ]);
    }
}
