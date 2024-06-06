<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Exceptions\EmailAlreadySubscribedToNewsletter;
use CeskaKruta\Web\FormData\DeliveryFormData;
use CeskaKruta\Web\FormData\SubscribeNewsletterFormData;
use CeskaKruta\Web\FormType\DeliveryFormType;
use CeskaKruta\Web\FormType\SubscribeNewsletterFormType;
use CeskaKruta\Web\Message\SubscribeNewsletter;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class HomepageController extends AbstractController
{
    public function __construct(
        private readonly CartStorage $cartStorage,
    ) {
    }

    #[Route(path: '/', name: 'homepage', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $deliveryFormData = DeliveryFormData::fromAddress($this->cartStorage->getDeliveryAddress());
        $deliveryForm = $this->createForm(DeliveryFormType::class, $deliveryFormData, [
            'action' => $this->generateUrl('order_delivery'),
        ]);

        return $this->render('homepage.html.twig', [
            'delivery_form' => $deliveryForm,
        ]);
    }
}
