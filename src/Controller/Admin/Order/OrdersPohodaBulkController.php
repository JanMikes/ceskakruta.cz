<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Order;

use CeskaKruta\Web\Message\Admin\Order\SendOrderToPohoda;
use CeskaKruta\Web\Services\Admin\OrderListTarget;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * "Označené poslat do Pohody": every order is sent on its own (own transaction), a failure does not stop the rest.
 */
final class OrdersPohodaBulkController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/objednavky/pohoda', name: 'admin_orders_pohoda_bulk', methods: ['POST'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin-action', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $orderIds = array_values(array_unique(array_map('intval', array_filter(
            $request->request->all('orders'),
            static fn (mixed $id): bool => is_numeric($id),
        ))));

        $sent = 0;
        $failed = [];

        foreach ($orderIds as $orderId) {
            try {
                $this->bus->dispatch(new SendOrderToPohoda($orderId, $admin->id));
                $sent++;
            } catch (HandlerFailedException) {
                $failed[] = $orderId;
            }
        }

        if ($sent > 0 || $failed === []) {
            $this->addFlash('success', sprintf('Propsáno do Pohody: %d objednávek', $sent));
        }

        if ($failed !== []) {
            $this->addFlash('danger', 'Nepodařilo se propsat do Pohody objednávky: ' . implode(', ', $failed));
        }

        return $this->redirect(OrderListTarget::fromRequest($request));
    }
}
