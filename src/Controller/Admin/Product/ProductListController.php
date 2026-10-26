<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Product;

use CeskaKruta\Web\Query\Admin\CountInactiveRecords;
use CeskaKruta\Web\Query\Admin\GetAdminProducts;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminProducts $getAdminProducts,
        readonly private CountInactiveRecords $countInactiveRecords,
    ) {
    }

    #[Route(path: '/admin/produkty', name: 'admin_products')]
    public function __invoke(): Response
    {
        return $this->render('admin/product/list.html.twig', [
            'products' => $this->getAdminProducts->all(),
            'inactive_count' => $this->countInactiveRecords->count(AdminTable::Product),
        ]);
    }
}
