<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Place;

use CeskaKruta\Web\Query\Admin\CountInactiveRecords;
use CeskaKruta\Web\Query\Admin\GetAdminPlaceClosedDays;
use CeskaKruta\Web\Query\Admin\GetAdminPlaces;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PlaceListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminPlaces $getAdminPlaces,
        readonly private GetAdminPlaceClosedDays $getAdminPlaceClosedDays,
        readonly private CountInactiveRecords $countInactiveRecords,
    ) {
    }

    #[Route(path: '/admin/prodejny', name: 'admin_places')]
    public function __invoke(): Response
    {
        return $this->render('admin/place/list.html.twig', [
            'places' => $this->getAdminPlaces->listed(),
            'closed_days' => $this->getAdminPlaceClosedDays->recent(),
            'inactive_count' => $this->countInactiveRecords->count(AdminTable::Place),
        ]);
    }
}
