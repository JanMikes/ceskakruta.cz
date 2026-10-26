<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Order;

use CeskaKruta\Web\Exceptions\RecordNotFound;
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

final class OrderPohodaController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/objednavky/{id}/pohoda', name: 'admin_order_pohoda', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        if (!$this->isCsrfTokenValid('admin-action', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $this->bus->dispatch(new SendOrderToPohoda($id, $admin->id));

            $this->addFlash('success', 'Objednávka propsána do Pohody');
        } catch (HandlerFailedException $handlerFailedException) {
            if ($handlerFailedException->getPrevious() instanceof RecordNotFound) {
                throw $this->createNotFoundException();
            }

            $this->addFlash('danger', sprintf('Objednávku %d se nepodařilo propsat do Pohody.', $id));
        }

        return $this->redirect(OrderListTarget::fromRequest($request) . '#item-' . $id);
    }
}
