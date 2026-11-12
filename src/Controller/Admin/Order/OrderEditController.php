<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Order;

use CeskaKruta\Web\Exceptions\BoxxiRequestFailed;
use CeskaKruta\Web\Exceptions\OrderItemInvalid;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\OrderEditFormData;
use CeskaKruta\Web\FormType\Admin\OrderEditFormType;
use CeskaKruta\Web\Message\Admin\Order\EditOrder;
use CeskaKruta\Web\Query\Admin\GetAdminOrderProducts;
use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use CeskaKruta\Web\Query\Admin\GetAdminPlaces;
use CeskaKruta\Web\Value\Admin\AdminOrderDetailItem;
use CeskaKruta\Web\Value\Admin\AdminOrderRecord;
use CeskaKruta\Web\Value\Admin\Order\AdminOrderProduct;
use CeskaKruta\Web\Value\Admin\Order\BoxxiEditOutcome;
use CeskaKruta\Web\Value\Admin\Order\OrderEditResult;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Edit an order (header, delivery, items). A CoolBalík order with a shipment continues to the Boxxi form, where the
 * recalculated values are confirmed and the shipment updated.
 */
final class OrderEditController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        readonly private GetAdminOrders $getAdminOrders,
        readonly private GetAdminOrderProducts $getAdminOrderProducts,
        readonly private GetAdminPlaces $getAdminPlaces,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/objednavky/{id}/upravit', name: 'admin_order_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $record = $this->getAdminOrders->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_orders');
        }

        $items = $this->getAdminOrders->detailItems($id);
        $products = $this->getAdminOrderProducts->all();
        $formData = OrderEditFormData::fromRecord($record, $items);
        $form = $this->createForm(OrderEditFormType::class, $formData, [
            'places' => $this->placeChoices($record),
            'products' => $this->productChoices($products, $items),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $this->deliveryAddressFilled($form, $formData)) {
            try {
                $result = $this->handle(EditOrder::fromFormData($id, $admin->id, $formData));
                assert($result instanceof OrderEditResult);

                return $this->redirectAfterSave($id, $result);
            } catch (HandlerFailedException $handlerFailedException) {
                $previous = $handlerFailedException->getPrevious();

                if ($previous instanceof OrderItemInvalid && $form->get('items')->has((string) $previous->itemKey)) {
                    $form->get('items')->get((string) $previous->itemKey)->addError(new FormError($previous->getMessage()));
                } elseif ($previous instanceof RecordNotFound) {
                    $form->get('place_id')->addError(new FormError('Místo neexistuje.'));
                } elseif ($previous instanceof BoxxiRequestFailed) {
                    $this->addFlash('danger', 'Zásilku v Boxxi se nepodařilo zrušit (už může být na cestě), nic nebylo uloženo.');
                } else {
                    throw $handlerFailedException;
                }
            }
        }

        return $this->render('admin/order/edit.html.twig', [
            'record' => $record,
            'form' => $form,
            'products' => $products,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    private function redirectAfterSave(int $id, OrderEditResult $result): Response
    {
        $this->addFlash('success', 'Objednávka uložena');

        if ($result->inPohoda) {
            $this->addFlash('warning', 'Objednávka už je v Pohodě – změnu je potřeba udělat i tam ručně.');
        }

        match ($result->boxxi) {
            BoxxiEditOutcome::Cancelled => $this->addFlash('info', 'Zásilka v Boxxi byla zrušena, objednávka už není rozvoz CoolBalík.'),
            BoxxiEditOutcome::Manual => $this->addFlash('warning', 'Objednávka byla do Boxxi propsána bez čísla zásilky – změnu je potřeba udělat v Boxxi ručně.'),
            BoxxiEditOutcome::NeedsUpdate => $this->addFlash('info', 'Zkontrolujte přepočítané hodnoty a aktualizujte zásilku v Boxxi.'),
            BoxxiEditOutcome::None => null,
        };

        if ($result->noteNotInBoxxi) {
            $this->addFlash('warning', 'Poznámku Boxxi změnit neumí. Pokud ji musí vidět řidič, zrušte zásilku a propište ji znovu.');
        }

        if ($result->emailSent === true) {
            $this->addFlash('success', 'Zákazníkovi byl odeslán e-mail se změnami.');
        } elseif ($result->emailSent === false) {
            $this->addFlash('danger', 'E-mail zákazníkovi se nepodařilo odeslat, objednávka je ale uložená.');
        }

        if ($result->boxxi === BoxxiEditOutcome::NeedsUpdate) {
            return $this->redirectToRoute('admin_order_boxxi', ['id' => $id]);
        }

        return $this->redirectToRoute('admin_order_detail', ['id' => $id]);
    }

    /**
     * Delivery places (`force_delivery_address`) need the full address.
     *
     * @param FormInterface<OrderEditFormData> $form
     */
    private function deliveryAddressFilled(FormInterface $form, OrderEditFormData $formData): bool
    {
        try {
            $place = $this->getAdminPlaces->oneById((int) $formData->placeId);
        } catch (RecordNotFound) {
            $form->get('place_id')->addError(new FormError('Místo neexistuje.'));

            return false;
        }

        if (!$place->forceDeliveryAddress) {
            return true;
        }

        $valid = true;

        foreach (['delivery_street' => $formData->deliveryStreet, 'delivery_city' => $formData->deliveryCity, 'delivery_postal_code' => $formData->deliveryPostalCode] as $field => $value) {
            if ($value === null || trim($value) === '') {
                $form->get($field)->addError(new FormError('Pro rozvoz je adresa povinná.'));
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Active places + the order's current place (even if inactive now).
     *
     * @return array<string, int> name => id
     */
    private function placeChoices(AdminOrderRecord $record): array
    {
        $places = $this->getAdminPlaces->namesForChoice();
        $current = $record->order->place;

        if ($current->id !== 0 && !isset($places[$current->id])) {
            $places[$current->id] = $current->name . ' (neaktivní)';
        }

        $choices = [];
        foreach ($places as $placeId => $name) {
            // Same names would collapse in name => id choices
            $choices[isset($choices[$name]) ? $name . ' #' . $placeId : $name] = $placeId;
        }

        return $choices;
    }

    /**
     * Active products with a price + products already in the order, grouped for the select.
     *
     * @param array<int, AdminOrderProduct> $products
     * @param list<AdminOrderDetailItem> $items
     * @return array<string, array<string, int>>
     */
    private function productChoices(array $products, array $items): array
    {
        $inOrder = array_map(static fn (AdminOrderDetailItem $item): int => $item->item->productId, $items);
        $choices = ['Celé kusy + půlky' => [], 'Ostatní' => []];

        foreach ($products as $product) {
            if (!in_array($product->id, $inOrder, true) && (!$product->active || $product->price === null)) {
                continue;
            }

            $group = $product->isTurkey() ? 'Celé kusy + půlky' : 'Ostatní';
            $label = sprintf('%s (%s)%s', $product->name, $product->unit, $product->active ? '' : ' – neaktivní');
            $choices[$group][isset($choices[$group][$label]) ? $label . ' #' . $product->id : $label] = $product->id;
        }

        return $choices;
    }
}
