<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Actuality;

use CeskaKruta\Web\Query\Admin\CountInactiveRecords;
use CeskaKruta\Web\Query\Admin\GetAdminActualities;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActualityListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminActualities $getAdminActualities,
        readonly private CountInactiveRecords $countInactiveRecords,
    ) {
    }

    #[Route(path: '/admin/aktuality', name: 'admin_actualities')]
    public function __invoke(): Response
    {
        return $this->render('admin/actuality/list.html.twig', [
            'actualities' => $this->getAdminActualities->all(),
            'inactive_count' => $this->countInactiveRecords->count(AdminTable::Actuality),
        ]);
    }
}
