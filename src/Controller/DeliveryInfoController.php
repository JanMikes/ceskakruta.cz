<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\DeliveryFormData;
use CeskaKruta\Web\FormType\DeliveryFormType;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeliveryInfoController extends AbstractController
{
    public function __construct(
        readonly private CartStorage $cartStorage,
    ) {
    }

    #[Route(path: '/jak-funguje-nas-rozvoz', name: 'delivery_info', methods: ['GET'])]
    public function __invoke(): Response
    {
        $data = DeliveryFormData::fromAddress($this->cartStorage->getDeliveryAddress());
        $deliveryForm = $this->createForm(DeliveryFormType::class, $data, [
            'action' => $this->generateUrl('order_delivery'),
        ]);

        return $this->render('delivery_info.html.twig', [
            'delivery_form' => $deliveryForm,
        ]);
    }
}
