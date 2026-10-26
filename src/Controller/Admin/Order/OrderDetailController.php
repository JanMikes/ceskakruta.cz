<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Order;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderDetailController extends AbstractController
{
    public function __construct(
        readonly private GetAdminOrders $getAdminOrders,
    ) {
    }

    #[Route(path: '/admin/objednavky/{id}', name: 'admin_order_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id): Response
    {
        try {
            $record = $this->getAdminOrders->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_orders');
        }

        return $this->render('admin/order/detail.html.twig', [
            'record' => $record,
            'items' => $this->getAdminOrders->detailItems($id),
        ]);
    }
}
