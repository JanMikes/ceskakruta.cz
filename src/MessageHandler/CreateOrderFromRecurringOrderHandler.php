<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\UnsupportedDeliveryToPostalCode;
use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\Message\CreateOrderFromRecurringOrder;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Repository\RecurringOrderRepository;
use CeskaKruta\Web\Services\CeskaKrutaDelivery;
use CeskaKruta\Web\Services\CoolBalikDelivery;
use CeskaKruta\Web\Services\OrderPriceCalculator;
use CeskaKruta\Web\Services\OrderService;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Services\UserService;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\CompanyBillingInfo;
use CeskaKruta\Web\Value\Place;
use CeskaKruta\Web\Value\ProductInCart;
use CeskaKruta\Web\Value\User;
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
        private GetPlaces $getPlaces,
        private UserProvider $userProvider,
        private GetProducts $getProducts,
        private UserService $userService,
        private CeskaKrutaDelivery $ceskaKrutaDelivery,
        private CoolBalikDelivery $coolBalikDelivery,
    ) {
    }

    /**
     * @throws UnsupportedDeliveryToPostalCode
     */
    public function __invoke(CreateOrderFromRecurringOrder $message): void
    {
        $recurringOrder = $this->recurringOrderRepository->get($message->recurringOrderId);
        $email = $this->userService->getEmailById($recurringOrder->userId);
        $user = $this->userProvider->loadUserByIdentifier($email);

        if ($user->hasFilledDeliveryAddress() === false) {
            return;
        }

        $products = $this->getProducts->all();

        $dayOfWeek = $recurringOrder->dayOfWeek;
        $dateTo = new \DateTimeImmutable();
        $currentWeekday = (int) $dateTo->format('N');

        $daysToAdd = ($dayOfWeek - $currentWeekday + 7) % 7;
        if ($daysToAdd === 0) {
            $daysToAdd = 7; // If today, skip to next week
        }

        $dateTo = $dateTo->modify("+{$daysToAdd} days");
        $place = $this->getDeliveryPlace($user);

        $orderData = new OrderFormData(
            name: $user->name ?? '',
            email: $user->email,
            phone: $user->phone ?? '',
            payByCard: true,
            note: 'Opakovaná objednávka',
            subscribeToNewsletter: false,
        );

        $deliveryAddress = new Address(
            street: $user->deliveryStreet ?? '',
            city: $user->deliveryCity ?? '',
            postalCode: $user->deliveryZip ?? '',
        );

        $companyBillingInfo = new CompanyBillingInfo(
            companyName: $user->companyName ?? '',
            companyId: $user->companyId ?? '',
            companyVatId: $user->companyVatId ?? '',
            address: new Address(
                street: $user->invoicingStreet ?? '',
                city: $user->invoicingCity ?? '',
                postalCode: $user->invoicingZip ?? '',
            ),
        );

        $items = [];

        foreach ($recurringOrder->items as $item) {
            $product = $products[$item->productId];

            $items[] = ProductInCart::createFromRecurringOrderItem($item, $product);
        }

        $totalPrice = OrderPriceCalculator::totalPrice($items, null, $place);
        $totalItemsPriceWithoutDiscount = OrderPriceCalculator::totalItemsPriceWithoutDiscount($items);

        $orderId = $this->orderService->createOrder(
            userId: $user->id,
            date: $dateTo,
            orderData: $orderData,
            placeId: $place->id,
            deliveryAddress: $deliveryAddress,
            items: $items,
            totalPrice: $totalPrice->amount,
            source: 'Opakovaná objednávka',
            companyBillingInfo: $companyBillingInfo,
        );

        $templateVariables = [
            'items' => $items,
            'order_id' => $orderId,
            'place' => $place,
            'order_data' => $orderData,
            'date_to' => $dateTo,
            'contains_turkey' => false,
            'contains_non_turkey' => true,
            'coupon' => null,
            'total_price_without_discount' => $totalPrice,
            'total_price' => $totalPrice,
            'delivery_price' => OrderPriceCalculator::getDeliveryPrice($totalItemsPriceWithoutDiscount, $place),
            'packing_price' => OrderPriceCalculator::getPackingPrice(),
            'delivery_address' => $deliveryAddress,
            'is_free_delivery' => OrderPriceCalculator::isFreeDelivery($totalItemsPriceWithoutDiscount, $place),
            'company_billing_info' => $companyBillingInfo,
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
            ->to($user->email)
            ->subject('Rekapitulace objednávky č. ' . $orderId)
            ->htmlTemplate('emails/user_order_recapitulation.html.twig')
            ->context($templateVariables);

        $email->getHeaders()->addTextHeader('X-Transport', 'orders');

        $this->mailer->send($email);
    }

    /**
     * @throws UnsupportedDeliveryToPostalCode
     */
    public function getDeliveryPlace(User $user): Place
    {
        assert($user->deliveryZip !== null);

        $placeId = $user->preferredPlaceId;
        $deliveryPlacesIds = [CeskaKrutaDelivery::DELIVERY_PLACE_ID, CoolBalikDelivery::DELIVERY_PLACE_ID];

        if (in_array($placeId, $deliveryPlacesIds, true) === false) {
            if ($this->ceskaKrutaDelivery->canDeliverToPostalCode($user->deliveryZip)) {
                $placeId = $this->ceskaKrutaDelivery::DELIVERY_PLACE_ID;
            } elseif ($this->coolBalikDelivery->canDeliverToPostalCode($user->deliveryZip)) {
                $placeId = $this->coolBalikDelivery::DELIVERY_PLACE_ID;
            }
        }

        if ($placeId === null) {
            throw new UnsupportedDeliveryToPostalCode();
        }

        return $this->getPlaces->oneById($placeId);
    }
}
