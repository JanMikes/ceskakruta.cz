<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Exceptions\UnsupportedDeliveryToPostalCode;
use CeskaKruta\Web\FormData\DeliveryFormData;
use CeskaKruta\Web\FormType\DeliveryFormType;
use CeskaKruta\Web\Message\ChooseDelivery;
use CeskaKruta\Web\Message\ChoosePickupPlace;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\Place;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class OrderDeliveryController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
        readonly private CartStorage $cartStorage,
        readonly private GetPlaces $getPlaces,
    ) {
    }

    #[Route(path: '/objednavka/zpusob-doruceni', name: 'order_delivery')]
    #[Route(path: '/objednavka/vybrat-misto-odberu/{placeId}', name: 'choose_order_pickup_place')]
    public function __invoke(Request $request, null|int $placeId = null): Response
    {
        if ($placeId !== null) {
            $this->bus->dispatch(
                new ChoosePickupPlace($placeId),
            );

            return $this->redirectToRoute('order_available_dates');
        }

        $formData = DeliveryFormData::fromAddress($this->cartStorage->getDeliveryAddress());
        $deliveryForm = $this->createForm(DeliveryFormType::class, $formData);
        $deliveryForm->handleRequest($request);

        if ($deliveryForm->isSubmitted() && $deliveryForm->isValid()) {
            try {
                $this->bus->dispatch(
                    new ChooseDelivery(
                        new Address(
                            street: $formData->street,
                            city: $formData->city,
                            postalCode: $formData->postalCode,
                        ),
                    ),
                );
            } catch (HandlerFailedException $handlerFailedException) {
                $realException = $handlerFailedException->getPrevious();

                if ($realException instanceof UnsupportedDeliveryToPostalCode) {
                    $this->addFlash('danger', 'Je nám líto, na tuto adresu aktuálně nerozvážíme. Máte-li zájem o doručení, napište nám na <a class="text-decoration-underline" href="mailto:info@ceskakruta.cz">info@ceskakruta.cz</a>.');

                    $referer = $request->headers->get('referer');

                    if (!is_string($referer)) {
                        return $this->redirectToRoute('order_delivery');
                    }

                    return $this->redirect($referer);
                } else {
                    throw $handlerFailedException;
                }
            }

            return $this->redirectToRoute('order_available_dates');
        }

        $pickupPlaces = array_filter(
            $this->getPlaces->all(),
            static fn (Place $place): bool => $place->isDelivery === false,
        );

        return $this->render('order_delivery.html.twig', [
            'delivery_form' => $deliveryForm,
            'places' => $pickupPlaces,
        ]);
    }
}
