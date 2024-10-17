<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Components;

use CeskaKruta\Web\Exceptions\UnsupportedDeliveryToPostalCode;
use CeskaKruta\Web\FormData\DeliveryFormData;
use CeskaKruta\Web\FormType\DeliveryFormType;
use CeskaKruta\Web\Message\ChooseDelivery;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Value\Address;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DeliveryForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public null|string $error = null;

    #[LiveProp]
    public bool $isOrder = false;

    public function __construct(
        readonly private MessageBusInterface $bus,
        readonly private CartStorage $cartStorage,
    ) {
    }

    /**
     * @return FormInterface<DeliveryFormData>
     */
    protected function instantiateForm(): FormInterface
    {
        $formData = DeliveryFormData::fromAddress($this->cartStorage->getDeliveryAddress());

        return $this->createForm(DeliveryFormType::class, $formData);
    }

    #[LiveAction]
    public function handleSubmit()
    {
        $this->submitForm();

        /** @var DeliveryFormData $data */
        $data = $this->getForm()->getData();

        try {
            $this->bus->dispatch(
                new ChooseDelivery(
                    new Address(
                        street: $data->street,
                        city: $data->city,
                        postalCode: $data->postalCode,
                    ),
                ),
            );
        } catch (HandlerFailedException $handlerFailedException) {
            $realException = $handlerFailedException->getPrevious();

            if ($realException instanceof UnsupportedDeliveryToPostalCode) {
                $this->error = 'Je nám líto, na tuto adresu aktuálně nerozvážíme. Máte-li zájem o doručení, napište nám na <a class="text-decoration-underline" href="mailto:info@ceskakruta.cz">info@ceskakruta.cz</a>.';
                return;
            }

            throw $handlerFailedException;
        }

        return $this->redirectToRoute('order_available_dates');
    }
}
