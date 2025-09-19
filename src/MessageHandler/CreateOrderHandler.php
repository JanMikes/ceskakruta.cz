<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\CreateOrder;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\OrderPriceCalculator;
use CeskaKruta\Web\Services\OrderService;
use CeskaKruta\Web\Services\ProductTypesSorter;
use CeskaKruta\Web\Value\ProductInCart;
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
    ) {
    }

    public function __invoke(CreateOrder $message): void
    {
        $orderId = $this->orderService->createOrderFromCart($message->userId);

        $this->cartStorage->storeLastOrderId($orderId);

        $address = $this->cartService->getOrderData()?->email;
        assert($address !== null);

        $items = $this->cartService->getItems();
        $items = ProductInCart::sortItemsByType($items);
        [$itemsTurkey, $itemsMeat, $itemsOther] = ProductTypesSorter::sort($items);

        $templateVariables = [
            'items' => $items,
            'items_turkey' => $itemsTurkey,
            'items_meat' => $itemsMeat,
            'items_other' => $itemsOther,
            'order_id' => $orderId,
            'place' => $this->cartService->getPlace(),
            'order_data' => $this->cartStorage->getOrderData(),
            'date_to' => $this->cartService->getDate(),
            'contains_turkey' => $this->cartService->containsTurkey(),
            'contains_non_turkey' => $this->cartService->containsNonTurkey(),
            'coupon' => $this->cartService->getCoupon(),
            'total_price_without_discount' => $this->cartService->totalPriceWithoutDiscount(),
            'total_price' => $this->cartService->totalPrice(),
            'delivery_price' => $this->cartService->getDeliveryPrice(),
            'packing_price' => OrderPriceCalculator::getPackingPrice(),
            'delivery_address' => $this->cartService->getDeliveryAddress(),
            'is_free_delivery' => $this->cartService->isFreeDelivery(),
            'company_billing_info' => null,
            'show_prices' => true,
            'is_ceskakruta' => false,
        ];

        // Email for the admin
        $email = (new TemplatedEmail())
            ->from('objednavky@ceskakruta.cz')
            ->to('info@ceskakruta.cz')
            ->subject('Rekapitulace objednávky č. ' . $orderId)
            ->htmlTemplate('emails/admin_order_recapitulation.html.twig')
            ->context($templateVariables);

        $email->getHeaders()->addTextHeader('X-Transport', 'orders');

        $this->mailer->send($email);

        // Email for the user
        $email = (new TemplatedEmail())
            ->from('objednavky@ceskakruta.cz')
            ->to($address)
            ->subject('Rekapitulace objednávky č. ' . $orderId)
            ->htmlTemplate('emails/user_order_recapitulation.html.twig')
            ->context($templateVariables);

        $email->getHeaders()->addTextHeader('X-Transport', 'orders');

        $this->mailer->send($email);

        $this->cartStorage->clear();
    }
}
