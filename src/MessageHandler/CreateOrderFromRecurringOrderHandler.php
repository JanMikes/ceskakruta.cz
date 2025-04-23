<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\Message\CreateOrderFromRecurringOrder;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Repository\RecurringOrderRepository;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\OrderService;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Services\UserService;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\ProductInCart;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateOrderFromRecurringOrderHandler
{
    public function __construct(
        private RecurringOrderRepository $recurringOrderRepository,
        private OrderService $orderService,
        private MailerInterface $mailer,
        private CartService $cartService,
        private GetPlaces $getPlaces,
        private UserProvider $userProvider,
        private GetProducts $getProducts,
        private UserService $userService,
    ) {
    }

    public function __invoke(CreateOrderFromRecurringOrder $message): void
    {
        $recurringOrder = $this->recurringOrderRepository->get($message->recurringOrderId);
        $email = $this->userService->getEmailById($recurringOrder->userId);
        $user = $this->userProvider->loadUserByIdentifier($email);
        $products = $this->getProducts->all();

        // TODO: $dateTo = find closest future day to which order
        $dateTo = new \DateTimeImmutable();

        $preferredPlaceId = $user->preferredPlaceId;
        assert($preferredPlaceId !== null);

        // TODO
        $totalPrice = 0;

        $orderData = new OrderFormData(
            name: $user->name,
            email: $user->email,
            phone: $user->phone,
            payByCard: true,
            note: 'Opakovaná objednávka',
            subscribeToNewsletter: false,
        );

        $deliveryAddress = new Address(
            street: $user->deliveryStreet,
            city: $user->deliveryCity,
            postalCode: $user->deliveryZip,
        );

        $items = [];

        foreach ($recurringOrder->items as $item) {
            $product = $products[$item->productId];

            $items[] = ProductInCart::createFromRecurringOrderItem($item, $product);
        }

        $orderId = $this->orderService->createOrder(
            userId: $user->id,
            date: $dateTo,
            orderData: $orderData,
            placeId: $preferredPlaceId,
            deliveryAddress: $deliveryAddress,
            items: $items,
            totalPrice: $totalPrice,
            source: 'Opakovaná objednávka',
        );

        // Email for the admin
        /*
        $email = (new TemplatedEmail())
            ->from('objednavky@ceskakruta.cz')
            ->to('info@ceskakruta.cz')
            ->subject('Rekapitulace objednávky č. ' . $orderId)
            ->htmlTemplate('emails/admin_order_recapitulation.html.twig')
            ->context([
                'items' => $this->cartService->getItems(),
                'places' => $this->getPlaces->all(),
                'order_id' => $orderId,
            ]);

        $email->getHeaders()->addTextHeader('X-Transport', 'orders');

        $this->mailer->send($email);
        */

        // Email for the user
        /*
        $email = (new TemplatedEmail())
            ->from('objednavky@ceskakruta.cz')
            ->to($user->email)
            ->subject('Rekapitulace objednávky č. ' . $orderId)
            ->htmlTemplate('emails/user_order_recapitulation.html.twig')
            ->context([
                'items' => $this->cartService->getItems(),
                'places' => $this->getPlaces->all(),
                'order_id' => $orderId,
            ]);

        $email->getHeaders()->addTextHeader('X-Transport', 'orders');

        $this->mailer->send($email);
        */
    }
}
