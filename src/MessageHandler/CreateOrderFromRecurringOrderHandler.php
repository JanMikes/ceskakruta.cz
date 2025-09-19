<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Entity\RecurringOrderItem;
use CeskaKruta\Web\Exceptions\UnsupportedDeliveryToPostalCode;
use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\Message\CreateOrderFromRecurringOrder;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Repository\RecurringOrderRepository;
use CeskaKruta\Web\Services\CeskaKrutaDelivery;
use CeskaKruta\Web\Services\CeskaKrutaShopDelivery;
use CeskaKruta\Web\Services\CoolBalikDelivery;
use CeskaKruta\Web\Services\OrderPriceCalculator;
use CeskaKruta\Web\Services\OrderService;
use CeskaKruta\Web\Services\ProductTypesSorter;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Services\UserService;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\CompanyBillingInfo;
use CeskaKruta\Web\Value\Place;
use CeskaKruta\Web\Value\Product;
use CeskaKruta\Web\Value\ProductInCart;
use CeskaKruta\Web\Value\User;
use CeskaKruta\Web\Value\Week;
use Psr\Clock\ClockInterface;
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
        private ClockInterface $clock,
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


        $dayOfWeek = $recurringOrder->dayOfWeek;
        $dateTo = $this->clock->now();
        $currentWeekday = (int) $dateTo->format('N');

        $daysToAdd = ($dayOfWeek - $currentWeekday + 7) % 7;
        if ($daysToAdd === 0) {
            $daysToAdd = 7; // If today, skip to next week
        }

        $dateTo = $dateTo->modify("+{$daysToAdd} days");
        $week = new Week((int) $dateTo->format('W'), (int) $dateTo->format('Y'));
        $products = $this->getProducts->all($week);

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
            $product = $products[$item->productId] ?? null;

            if ($product === null) {
                continue;
            }

            // Special handling for turkey products
            if ($product->isTurkey) {
                $turkeyProduct = $this->selectAvailableTurkeyProduct($products);
                if ($turkeyProduct === null) {
                    // No turkey available for this week, skip this product
                    continue;
                }

                // Calculate turkey amount based on requested kg weight
                $requestedKg = $this->calculateRequestedTurkeyWeight($item);
                if ($requestedKg <= 0) {
                    continue;
                }

                $turkeyAmount = $this->calculateTurkeyAmount($turkeyProduct, $requestedKg);
                if ($turkeyAmount <= 0) {
                    continue;
                }

                // Create ProductInCart directly for turkey with calculated amount
                // For turkey products, quantity represents number of pieces (turkeyAmount)
                // The note will include the original requested kg and actual turkey type selected
                $note = sprintf('%s%s%s%sPůvodní požadavek: %.1f kg, pokryje: %d ks',
                    trim((string) $item->note),
                    trim((string) $item->note) !== '' ? ' | ' : '',
                    $item->quantitiesAsNote(),
                    trim($item->note . $item->quantitiesAsNote()) !== '' ? ' | ' : '',
                    $requestedKg,
                    $turkeyAmount,
                );

                $items[] = new ProductInCart(
                    quantity: $turkeyAmount,
                    product: $turkeyProduct,
                    slice: $item->isSliced,
                    pack: $item->isPacked,
                    note: $note,
                );
            } else {
                $items[] = ProductInCart::createFromRecurringOrderItem($item, $product);
            }
        }

        $items = ProductInCart::sortItemsByType($items);

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

        $recurringOrder->updateLastOrdered($this->clock->now());

        [$itemsTurkey, $itemsMeat, $itemsOther] = ProductTypesSorter::sort($items);

        $templateVariables = [
            'items' => $items,
            'items_turkey' => $itemsTurkey,
            'items_meat' => $itemsMeat,
            'items_other' => $itemsOther,
            'contains_turkey' => count($itemsTurkey) > 0,
            'contains_non_turkey' => (count($itemsMeat) + count($itemsOther)) > 0,
            'order_id' => $orderId,
            'place' => $place,
            'order_data' => $orderData,
            'date_to' => $dateTo,
            'coupon' => null,
            'total_price_without_discount' => $totalPrice,
            'total_price' => $totalPrice,
            'delivery_price' => OrderPriceCalculator::getDeliveryPrice($totalItemsPriceWithoutDiscount, $place),
            'packing_price' => OrderPriceCalculator::getPackingPrice(),
            'delivery_address' => $deliveryAddress,
            'is_free_delivery' => OrderPriceCalculator::isFreeDelivery($totalItemsPriceWithoutDiscount, $place),
            'company_billing_info' => $companyBillingInfo,
            'show_prices' => false,
            'is_ceskakruta' => $user->isCeskakruta,
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
        $deliveryPlacesIds = [
            CeskaKrutaDelivery::DELIVERY_PLACE_ID,
            CeskaKrutaShopDelivery::DELIVERY_PLACE_ID,
            CoolBalikDelivery::DELIVERY_PLACE_ID,
        ];

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

    /**
     * Select available turkey product for given delivery date, preferring type 2 over type 1
     *
     * @param array<int, Product> $products
     */
    private function selectAvailableTurkeyProduct(array $products): ?Product
    {
        // First try to find type 2 turkey with weight data available
        foreach ($products as $product) {
            if ($product->isTurkey && $product->turkeyType === 2 && $product->weightFrom !== null && $product->weightTo !== null) {
                return $product;
            }
        }

        // Fallback to type 1 turkey with weight data available
        foreach ($products as $product) {
            if ($product->isTurkey && $product->turkeyType === 1 && $product->weightFrom !== null && $product->weightTo !== null) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Calculate requested turkey weight in kg from recurring order item
     */
    private function calculateRequestedTurkeyWeight(RecurringOrderItem $item): float
    {
        $totalKg = 0.0;

        // Add weight from package amounts
        foreach ($item->packages as $package) {
            $kgPerPackage = $package->sizeG / 1000;
            $totalKg += $kgPerPackage * $package->amount;
        }

        // Add weight from "other" amount - for turkey this represents kg directly
        $totalKg += $item->otherPackageSizeAmount;

        return $totalKg;
    }

    /**
     * Calculate how many turkey pieces are needed for requested weight
     */
    private function calculateTurkeyAmount(Product $turkeyProduct, float $requestedKg): int
    {
        if ($turkeyProduct->weightFrom === null || $turkeyProduct->weightTo === null) {
            return 0;
        }

        // Use average weight for calculation, but round up to ensure we meet the requested weight
        $averageWeight = ($turkeyProduct->weightFrom + $turkeyProduct->weightTo) / 2;

        // Calculate pieces needed and round up to ensure sufficient weight
        $piecesNeeded = (int) ceil($requestedKg / $averageWeight);

        return max(1, $piecesNeeded); // At least 1 piece
    }
}
