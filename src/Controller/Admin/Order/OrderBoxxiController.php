<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Order;

use CeskaKruta\Web\Exceptions\BoxxiRequestFailed;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\BoxxiFormData;
use CeskaKruta\Web\FormType\Admin\BoxxiFormType;
use CeskaKruta\Web\Message\Admin\Order\SendOrderToBoxxi;
use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use CeskaKruta\Web\Services\Boxxi\ShipmentWeightCalculator;
use CeskaKruta\Web\Value\Admin\BoxxiSyncResult;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Old `Order:coolBalik` page + `CoolBalikFormFactory`.
 */
final class OrderBoxxiController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        readonly private GetAdminOrders $getAdminOrders,
        readonly private ShipmentWeightCalculator $weightCalculator,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/objednavky/{id}/boxxi', name: 'admin_order_boxxi', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $record = $this->getAdminOrders->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_orders');
        }

        $formData = BoxxiFormData::fromOrder($record, $this->getAdminOrders->items($id), $this->weightCalculator);
        $form = $this->createForm(BoxxiFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $result = $this->handle(SendOrderToBoxxi::fromFormData($id, $admin->id, $formData));

                if ($result === BoxxiSyncResult::Created) {
                    $this->addFlash('success', 'Objednávka propsána do Boxxi');
                } elseif ($result === BoxxiSyncResult::Updated) {
                    $this->addFlash('success', 'Zásilka v Boxxi aktualizována');
                }

                return $this->redirect($this->generateUrl('admin_orders') . '#item-' . $id);
            } catch (HandlerFailedException $handlerFailedException) {
                if (!$handlerFailedException->getPrevious() instanceof BoxxiRequestFailed) {
                    throw $handlerFailedException;
                }

                $this->addFlash('danger', $record->boxxiShipmentNumber !== null
                    ? 'Zásilku se nepodařilo v Boxxi aktualizovat (už může být na cestě), nic nebylo uloženo.'
                    : 'Objednávku se nepodařilo propsat do Boxxi, nic nebylo uloženo. Zkuste to prosím znovu.');
            }
        }

        return $this->render('admin/order/boxxi.html.twig', [
            'record' => $record,
            'items' => $this->getAdminOrders->detailItems($id),
            'form' => $form,
        ], new Response(status: $form->isSubmitted() && !$form->isValid() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
