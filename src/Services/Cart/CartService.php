<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cart;

use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Query\GetCoupon;
use CeskaKruta\Web\Query\GetPlaceClosedDays;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Services\CeskaKrutaDelivery;
use CeskaKruta\Web\Services\CoolBalikDelivery;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\Coupon;
use CeskaKruta\Web\Value\Place;
use CeskaKruta\Web\Value\Price;
use CeskaKruta\Web\Value\ProductInCart;
use CeskaKruta\Web\Value\Week;
use DateTimeImmutable;

readonly final class CartService
{
    public function __construct(
        private CartStorage $storage,
        private GetProducts $getProducts,
        private GetColdProductsCalendar $getColdProductsCalendar,
        private GetPlaces $getPlaces,
        private CeskaKrutaDelivery $ceskaKrutaDelivery,
        private CoolBalikDelivery $coolBalikDelivery,
        private GetPlaceClosedDays $getPlaceClosedDays,
        private GetCoupon $getCoupon,
    ) {
    }

    public function isMinimalPriceMet(): bool
    {
        return $this->totalPrice()->amount >= $this->getMinimalPrice();
    }

    public function getDeliveryMinimalPrice(): int
    {
        return 1000;
    }

    public function getMinimalPrice(): int
    {
        if ($this->getPlace()?->isDelivery) {
            return $this->getDeliveryMinimalPrice();
        }

        return 0;
    }

    public function itemsCount(): int
    {
        return count($this->getItems());
    }

    public function getPickupPlace(): null|int
    {
        return $this->storage->getPickupPlace();
    }

    public function getDeliveryAddress(): null|Address
    {
        return $this->storage->getDeliveryAddress();
    }

    public function getDate(): null|DateTimeImmutable
    {
        $date = $this->storage->getDate();
        $place = $this->getPlace();

        if ($date === null || $place === null) {
            return null;
        }

        if ($this->isDateAvailable($date, $place->id) === false) {
            $this->storage->storeDate(null);
            return null;
        }

        return $date;
    }

    public function getPlace(): null|Place
    {
        $deliveryPlace = $this->getDeliveryPlace();

        if ($deliveryPlace !== null) {
            return $this->getPlaces->oneById($deliveryPlace);
        }

        $pickupPlace = $this->getPickupPlace();

        if ($pickupPlace !== null) {
            return $this->getPlaces->oneById($pickupPlace);
        }

        return null;
    }

    public function getCoupon(): null|Coupon
    {
        $coupon = $this->storage->getCoupon();

        if ($coupon !== null) {
            return $this->getCoupon->oneByCode($coupon);
        }

        return null;
    }

    /**
     * @return list<ProductInCart>
     */
    public function getItems(): array
    {
        $products = $this->getProducts->all();
        $calendar = $this->getColdProductsCalendar->all();
        $items = [];
        $week = $this->getWeek();
        $place = $this->getPlace();
        $placeForcedPack = $place->forcePacking ?? false;

        foreach ($this->storage->getItems() as $key => $item) {
            $product = $products[$item->productId];

            // check calendar, if any of products is not available then remove them
            if ($product->isTurkey === true && ($calendar[$week->year][$week->number][$product->turkeyType] ?? null) === null) {
                $this->storage->removeItem($key);
                continue;
            }

            $items[] = new ProductInCart(
                $item->quantity,
                $product,
                $item->slice,
                $placeForcedPack ?: $item->pack,
                $item->note,
            );
        }

        return $items;
    }

    public function totalPriceWithoutDiscount(): Price
    {
        $totalPrice = new Price(0);

        foreach ($this->getItems() as $item) {
            $product = $item->product;
            $unitPrice = $product->price();

            $totalPrice = $totalPrice->add((int) ($item->quantity * $unitPrice));

            if ($item->pack === true) {
                $totalPrice = $totalPrice->add($product->packPrice ?? 0);
            }
        }

        if ($totalPrice->amount > 0 && $this->getDeliveryAddress() !== null && $this->getDeliveryPlace() !== null) {
            $totalPrice = $totalPrice->add($this->getDeliveryPrice());
            $totalPrice = $totalPrice->add($this->getPackingPrice());
        }

        return $totalPrice;
    }

    public function totalPrice(): Price
    {
        $coupon = $this->getCoupon();
        $totalPrice = new Price(0);

        foreach ($this->getItems() as $item) {
            $product = $item->product;
            $totalPrice = $totalPrice->add($item->price($coupon));

            if ($item->pack === true) {
                $totalPrice = $totalPrice->add($product->packPrice ?? 0);
            }
        }

        if ($totalPrice->amount > 0 && $this->getDeliveryAddress() !== null && $this->getDeliveryPlace() !== null) {
            $totalPrice = $totalPrice->add($this->getDeliveryPrice());
            $totalPrice = $totalPrice->add($this->getPackingPrice());
        }

        return $totalPrice;
    }

    public function getDeliveryPrice(): int
    {
        return 195;
    }

    public function getPackingPrice(): int
    {
        return 35;
    }

    public function getOrderData(): null|OrderFormData
    {
        return $this->storage->getOrderData();
    }

    public function getLockedWeek(): null|Week
    {
        return $this->storage->getLockedWeek();
    }

    public function getWeek(): Week
    {
        return $this->storage->getWeek();
    }

    public function containsTurkey(): bool
    {
        foreach ($this->getItems() as $item) {
            if ($item->product->isTurkey === true) {
                return true;
            }
        }

        return false;
    }

    public function containsNonTurkey(): bool
    {
        foreach ($this->getItems() as $item) {
            if ($item->product->isTurkey === false) {
                return true;
            }
        }

        return false;
    }

    public function getDeliveryPlace(): null|int
    {
        return $this->storage->getDeliveryPlace();
    }

    public function removeItem(int $keyToRemove): void
    {
        $this->storage->removeItem($keyToRemove);

        if ($this->containsTurkey() === false) {
            $this->storage->storeLockedWeek(null, null);
        }
    }

    public function isOrderReadyToBePlaced(): bool
    {
        if ($this->getPlace() === null) {
            return false;
        }

        if ($this->getDate() === null) {
            return false;
        }

        if ($this->getOrderData() === null) {
            return false;
        }

        if ($this->storage->itemsCount() <= 0) {
            return false;
        }

        if ($this->isMinimalPriceMet() === false) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if anything was removed from the cart
     */
    public function removeUnavailableTurkeysAtWeek(int $week, int $year): bool
    {
        $lockedWeek = $this->getLockedWeek();

        if ($lockedWeek === null || $this->containsTurkey() === false) {
            return false;
        }

        $calendar = $this->getColdProductsCalendar->all();
        $removedAnything = false;

        // Can not use foreach directly, because keys gets always recalculated, so needs to be wrapped in a while loop
        for($i = 0; $i <= count($this->getItems()); $i++) {
            foreach ($this->getItems() as $key => $item) {
                // Check weight, if not matched, remove it
                if ($item->product->isTurkey === true) {
                    $originalWeightFrom = $calendar[$lockedWeek->year][$lockedWeek->number][$item->product->turkeyType]->weightFrom ?? null;
                    $originalWeightTo = $calendar[$lockedWeek->year][$lockedWeek->number][$item->product->turkeyType]->weightTo ?? null;
                    $newWeightFrom = $calendar[$year][$week][$item->product->turkeyType]->weightFrom ?? null;
                    $newWeightTo = $calendar[$year][$week][$item->product->turkeyType]->weightTo ?? null;

                    if ($originalWeightFrom !== $newWeightFrom || $originalWeightTo !== $newWeightTo) {
                        $this->storage->removeItem($key);
                        $removedAnything = true;
                        break;
                    }
                }
            }
        }

        return $removedAnything;
    }

    public function isDateAvailable(DateTimeImmutable $date, int $placeId): bool
    {
        $date = $date->setTime(0, 0, 0);
        $place = $this->getPlaces->oneById($placeId);

        $lockedWeek = $this->getLockedWeek();

        // Date is not available if cart contains cold turkey products and the weights does not match for locked and target week
        if ($lockedWeek !== null && $this->containsTurkey() === true) {
            $calendar = $this->getColdProductsCalendar->all();
            $year = (int) $date->format('Y');
            $week = (int) $date->format('W');

            foreach ($this->getItems() as $item) {
                // Check weight, if not matched, remove it
                if ($item->product->isTurkey === true) {
                    $originalWeightFrom = $calendar[$lockedWeek->year][$lockedWeek->number][$item->product->turkeyType]->weightFrom ?? null;
                    $originalWeightTo = $calendar[$lockedWeek->year][$lockedWeek->number][$item->product->turkeyType]->weightTo ?? null;
                    $newWeightFrom = $calendar[$year][$week][$item->product->turkeyType]->weightFrom ?? null;
                    $newWeightTo = $calendar[$year][$week][$item->product->turkeyType]->weightTo ?? null;

                    if ($originalWeightFrom !== $newWeightFrom || $originalWeightTo !== $newWeightTo) {
                        return false;
                    }
                }
            }
        }

        $today = (new DateTimeImmutable())->setTime(0, 0, 0);
        $weekDay = (int) $date->format('w'); // 0 = neděle, 1 = pondělí, ..., 6 = sobota
        $weekDay = $weekDay === 0 ? 7 : $weekDay; // Převedeme neděli na 7

        $allowDaysBefore = [
            1 => $place->day1AllowedDaysBefore,
            2 => $place->day2AllowedDaysBefore,
            3 => $place->day3AllowedDaysBefore,
            4 => $place->day4AllowedDaysBefore,
            5 => $place->day5AllowedDaysBefore,
            6 => $place->day6AllowedDaysBefore,
            7 => $place->day7AllowedDaysBefore,
        ];

        if ($placeId === 1 && $date->format('Y-m-d') === '2024-12-22') {
            $allowDaysBefore[7] = 3;
        }

        if ($placeId === 1 && $date->format('Y-m-d') === '2024-12-23') {
            $allowDaysBefore[1] = 4;
        }

        $postalCode = $this->getDeliveryAddress()?->postalCode;

        if ($postalCode !== null && $placeId === $this->ceskaKrutaDelivery::DELIVERY_PLACE_ID) {
            $allowedDays = $this->ceskaKrutaDelivery->getAllowedDaysForPostalCode($postalCode);

            // Set to null days that we do not deliver to the address
            foreach ($allowDaysBefore as $allowedDay => $daysBefore) {
                if (!in_array($allowedDay, $allowedDays, true)) {
                    $allowDaysBefore[$allowedDay] = null;
                }
            }
        }

        if ($postalCode !== null && $placeId === $this->coolBalikDelivery::DELIVERY_PLACE_ID) {
            $allowedDays = $this->coolBalikDelivery->getAllowedDaysForPostalCode($postalCode);

            // Set to null days that we do not deliver to the address
            foreach ($allowDaysBefore as $allowedDay => $daysBefore) {
                if (!in_array($allowedDay, $allowedDays, true)) {
                    $allowDaysBefore[$allowedDay] = null;
                }
            }
        }

        $daysBefore = $allowDaysBefore[$weekDay] ?? null;

        if ($daysBefore === null) {
            return false;
        }

        $skipDates = $this->getPlaceClosedDays->forPlace($placeId);

        if (in_array($date->format('Y-m-d'), $skipDates, true)) {
            return false;
        }

        // Zkontrolujeme, zda je datum dostatečně daleko od dnešního dne
        return $date->diff($today)->days >= $daysBefore;
    }
}
