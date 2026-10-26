<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Vendor;

use CeskaKruta\Web\Query\Admin\CountInactiveRecords;
use CeskaKruta\Web\Query\Admin\GetAdminVendors;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VendorListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminVendors $getAdminVendors,
        readonly private CountInactiveRecords $countInactiveRecords,
    ) {
    }

    #[Route(path: '/admin/prodejci', name: 'admin_vendors')]
    public function __invoke(): Response
    {
        return $this->render('admin/vendor/list.html.twig', [
            'vendors' => $this->getAdminVendors->all(),
            'inactive_count' => $this->countInactiveRecords->count(AdminTable::Vendor),
        ]);
    }
}
