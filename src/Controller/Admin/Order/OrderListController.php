<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Order;

use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use CeskaKruta\Web\Value\Admin\AdminOrderFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderListController extends AbstractController
{
    private const PER_PAGE = 50;

    public function __construct(
        readonly private GetAdminOrders $getAdminOrders,
    ) {
    }

    #[Route(path: '/admin/objednavky', name: 'admin_orders', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $filter = AdminOrderFilter::fromQuery($request->query);
        $total = $this->getAdminOrders->count($filter);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, $request->query->getInt('page', 1)));

        return $this->render('admin/order/list.html.twig', [
            'orders' => $this->getAdminOrders->filtered($filter, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'filter' => $filter,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ]);
    }
}
