<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\CreateOrder;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\OrderService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateOrderHandler
{
    public function __construct(
        private OrderService $orderService,
        private CartStorage $cartStorage,
        private MailerInterface $mailer,
        private CartService $cartService,
        private GetPlaces $getPlaces,
    ) {
    }

    public function __invoke(CreateOrder $message): void
    {
        $orderId = $this->orderService->createOrder($message->userId);

        $this->cartStorage->storeLastOrderId($orderId);

        $address = $this->cartService->getOrderData()?->email;
        assert($address !== null);

        $email = (new TemplatedEmail())
            ->to($address)
            ->addBcc('info@ceskakruta.cz')
            ->subject('Rekapitulace objednávky č. ' . $orderId)
            ->htmlTemplate('emails/user_order_recapitulation.html.twig')
            ->context([
                'items' => $this->cartService->getItems(),
                'places' => $this->getPlaces->all(),
                'order_id' => $orderId,
            ]);

        $email->getHeaders()->addTextHeader('X-Transport', 'orders');

        $this->mailer->send($email);

        $this->cartStorage->clear();
    }
}
