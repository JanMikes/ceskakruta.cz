<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Order;

use CeskaKruta\Web\Exceptions\BoxxiRequestFailed;
use CeskaKruta\Web\Exceptions\BoxxiShipmentNumberMissing;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Order\CancelBoxxiShipment;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * "Zrušit zásilku v Boxxi" button on the Boxxi page, back to the Boxxi page so the order can be sent again.
 */
final class OrderBoxxiCancelController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/objednavky/{id}/boxxi/zrusit', name: 'admin_order_boxxi_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        if (!$this->isCsrfTokenValid('admin-action', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $this->bus->dispatch(new CancelBoxxiShipment($id, $admin->id));

            $this->addFlash('success', 'Zásilka v Boxxi zrušena, objednávku lze propsat znovu');
        } catch (HandlerFailedException $handlerFailedException) {
            $previous = $handlerFailedException->getPrevious();

            if ($previous instanceof RecordNotFound) {
                throw $this->createNotFoundException();
            }

            if ($previous instanceof BoxxiShipmentNumberMissing) {
                $this->addFlash('danger', 'Číslo zásilky v Boxxi není uloženo, zásilku zrušte přímo u Boxxi.');
            } elseif ($previous instanceof BoxxiRequestFailed) {
                $this->addFlash('danger', 'Zásilku se nepodařilo v Boxxi zrušit (už může být na cestě), nic nebylo změněno.');
            } else {
                throw $handlerFailedException;
            }
        }

        return $this->redirectToRoute('admin_order_boxxi', ['id' => $id]);
    }
}
