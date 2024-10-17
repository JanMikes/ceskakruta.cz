<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Components;

use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\FormType\OrderFormType;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class OrderForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public function __construct(
        readonly private CartStorage $cartStorage,
        readonly private CartService $cartService,
        readonly private Security $security,
    ) {
    }

    /**
     * @return FormInterface<OrderFormData>
     */
    protected function instantiateForm(): FormInterface
    {
        /** @var null|User $user */
        $user = $this->security->getUser();

        $orderData = $this->cartStorage->getOrderData();

        if ($orderData === null) {
            $orderData = new OrderFormData();

            if ($user !== null) {
                $orderData->email = $user->email;
                $orderData->name = $user->name ?? '';
                $orderData->phone = $user->phone ?? '';
            }
        }

        return $this->createForm(OrderFormType::class, $orderData);
    }

    #[LiveAction]
    public function handleSubmit(): Response
    {
        $this->submitForm();

        /** @var OrderFormData $data */
        $data = $this->getForm()->getData();

        $this->cartStorage->storeOrderData($data);

        if ($this->cartService->isOrderReadyToBePlaced()) {
            return $this->redirectToRoute('order_recapitulation');
        } else {
            return $this->redirectToRoute('order_contact_info');
        }
    }
}
