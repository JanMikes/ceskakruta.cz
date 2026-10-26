<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Order;

use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use CeskaKruta\Web\Value\Admin\AdminOrderFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Old `OrderPresenter::actionExport`, but respects the list filter (PLAN §6 Q5), always all pages.
 */
final class OrderExportController extends AbstractController
{
    public function __construct(
        readonly private GetAdminOrders $getAdminOrders,
    ) {
    }

    #[Route(path: '/admin/objednavky/export.csv', name: 'admin_orders_export', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $csv = fopen('php://temp', 'r+');
        assert($csv !== false);

        fputcsv($csv, ['Číslo', 'Datum provedení', 'Zákazník', 'Cena', 'Místo', 'Datum', 'Poznámka'], ',', '"', '\\');

        foreach ($this->getAdminOrders->filtered(AdminOrderFilter::fromQuery($request->query)) as $record) {
            $order = $record->order;

            fputcsv($csv, [
                $order->id,
                $order->insertedAt->format('d.m.Y H:i'),
                sprintf('%s, %s, %s %s', $order->name, $order->deliveryStreet, $order->deliveryCity, $order->deliveryPostalCode),
                (string) $record->priceTotal,
                $order->place->name,
                $order->date->format('d.m.Y'),
                $order->note,
            ], ',', '"', '\\');
        }

        rewind($csv);
        $content = (string) stream_get_contents($csv);
        fclose($csv);

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'ceska-kruta-objednavky.csv'),
        ]);
    }
}
