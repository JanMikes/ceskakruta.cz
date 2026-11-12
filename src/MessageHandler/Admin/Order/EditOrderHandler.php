<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Order;

use CeskaKruta\Web\Exceptions\BoxxiRequestFailed;
use CeskaKruta\Web\Exceptions\OrderItemInvalid;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Order\EditOrder;
use CeskaKruta\Web\Message\Admin\Order\EditOrderItem;
use CeskaKruta\Web\Query\Admin\GetAdminOrderProducts;
use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use CeskaKruta\Web\Query\Admin\GetAdminPlaces;
use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use CeskaKruta\Web\Services\Admin\Order\AdminOrderPricing;
use CeskaKruta\Web\Services\Admin\Order\OrderChangeMailer;
use CeskaKruta\Web\Services\Boxxi\BoxxiShipments;
use CeskaKruta\Web\Value\Admin\AdminOrderDetailItem;
use CeskaKruta\Web\Value\Admin\AdminOrderPlace;
use CeskaKruta\Web\Value\Admin\AdminOrderRecord;
use CeskaKruta\Web\Value\Admin\Order\AdminOrderProduct;
use CeskaKruta\Web\Value\Admin\Order\BoxxiEditOutcome;
use CeskaKruta\Web\Value\Admin\Order\OrderEditResult;
use CeskaKruta\Web\Value\Admin\Order\OrderPrice;
use CeskaKruta\Web\Value\Admin\Order\PricedOrderItem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Saves the edited order (one transaction via the bus middleware):
 *  - items updated / added / soft-deleted (`del_flag`), prices by the web rules unless the admin set the unit price,
 *    whole turkeys without a weight class get the cold calendar class of the order week;
 *  - Boxxi: moved away from CoolBalík with a known shipment → the shipment is cancelled (a failed cancel rolls back the
 *    whole edit); still CoolBalík → the caller sends the admin to the Boxxi form to confirm the update;
 *  - Pohoda is not touched (out of scope), the result only says the order is there;
 *  - optional customer email, a failed send does not roll back the edit (only logged).
 */
#[AsMessageHandler]
readonly final class EditOrderHandler
{
    public function __construct(
        private GetAdminOrders $getAdminOrders,
        private GetAdminOrderProducts $getAdminOrderProducts,
        private GetAdminPlaces $getAdminPlaces,
        private GetColdProductsCalendar $getColdProductsCalendar,
        private AdminRecordRepository $repository,
        private BoxxiShipments $boxxiShipments,
        private OrderChangeMailer $changeMailer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws RecordNotFound
     * @throws OrderItemInvalid
     * @throws BoxxiRequestFailed
     */
    public function __invoke(EditOrder $message): OrderEditResult
    {
        $before = $this->getAdminOrders->oneById($message->orderId);
        $beforeItems = $this->getAdminOrders->detailItems($message->orderId);
        $adminPlace = $this->getAdminPlaces->oneById($message->placeId);
        $place = new AdminOrderPlace($adminPlace->id, $adminPlace->name, $adminPlace->isCoolBalik, $adminPlace->forceDeliveryAddress);
        $products = $this->getAdminOrderProducts->all();
        $couponPercent = $before->order->couponPercentValue;

        $existing = [];
        foreach ($beforeItems as $detailItem) {
            $existing[$detailItem->item->id] = $detailItem;
        }

        $rows = [];
        $priced = [];

        foreach ($message->items as $key => $item) {
            $product = $products[$item->productId] ?? throw new OrderItemInvalid($key, 'Produkt neexistuje.');
            $previous = $item->id !== null ? ($existing[$item->id] ?? null) : null;
            [$weightFrom, $weightTo] = $this->weights($key, $item, $product, $message->date);

            $pricePerUnit = $item->pricePerUnit ?? AdminOrderPricing::unitPrice($product, $couponPercent, $weightFrom, $weightTo)
                ?? throw new OrderItemInvalid($key, sprintf('Produkt „%s“ nemá cenu, zadejte cenu za jednotku.', $product->name));
            // Packing price as charged originally while the product stays the same
            $pricePacking = $previous !== null && $previous->item->productId === $product->id ? $previous->item->pricePacking : ($product->pricePack ?? 0);

            $line = new PricedOrderItem(
                pricePerUnit: $pricePerUnit,
                pricePerUnitBeforeDiscount: AdminOrderPricing::beforeDiscount($pricePerUnit, $couponPercent),
                amount: $item->amount,
                isPacked: $item->isPacked,
                pricePacking: $pricePacking,
                storedTotalPerAmount: $this->storedTotalPerAmount($previous, $product->id, $pricePerUnit, $weightFrom, $weightTo),
            );
            $priced[] = $line;

            $rows[] = [$previous, [
                'product_id' => $product->id,
                'product_tp_id' => $product->turkeyTypeId(),
                'is_sliced' => $item->isSliced,
                'is_packed' => $item->isPacked,
                'amount' => $item->amount,
                'price_per_unit' => $pricePerUnit,
                'price_packing' => $pricePacking,
                'price_total' => $line->priceTotal(),
                'weight_from' => $weightFrom,
                'weight_to' => $weightTo,
                'note' => $item->note,
            ]];
        }

        $price = AdminOrderPricing::orderPrice($priced, $place);

        $this->repository->update('order', $message->orderId, [
            'place_id' => $place->id,
            'date' => $message->date->format('Y-m-d'),
            'name' => $message->name,
            'phone' => $message->phone,
            'email' => $message->email,
            'delivery_street' => $message->deliveryStreet,
            'delivery_city' => $message->deliveryCity,
            'delivery_postal_code' => $message->deliveryPostalCode,
            'pay_by_card' => $message->payByCard,
            'note' => $message->note,
            'price_total' => $price->total(),
            'price_total_before_discount' => $couponPercent !== null && $couponPercent > 0 ? $price->totalBeforeDiscount() : $before->priceTotalBeforeDiscount,
        ], $message->adminId);

        foreach ($rows as [$previous, $data]) {
            if ($previous !== null) {
                $this->repository->update('order_item', $previous->item->id, $data, $message->adminId);
                unset($existing[$previous->item->id]);
            } else {
                $this->repository->insert('order_item', ['order_id' => $message->orderId] + $data, $message->adminId);
            }
        }

        foreach ($existing as $removed) {
            $this->repository->update('order_item', $removed->item->id, ['del_flag' => 1], $message->adminId);
        }

        [$boxxi, $noteNotInBoxxi] = $this->boxxi($before, $place, $message);

        return new OrderEditResult(
            boxxi: $boxxi,
            inPohoda: $before->pohodaSyncedAt !== null,
            noteNotInBoxxi: $noteNotInBoxxi,
            emailSent: $message->notifyCustomer ? $this->notify($before, $beforeItems, $price, $message->orderId) : null,
        );
    }

    /**
     * Existing item with the same product, unit price and weight class keeps its stored total per unit of amount.
     */
    private function storedTotalPerAmount(null|AdminOrderDetailItem $previous, int $productId, int $pricePerUnit, null|float $weightFrom, null|float $weightTo): null|float
    {
        if ($previous === null || $previous->item->amount <= 0.0) {
            return null;
        }

        $item = $previous->item;

        if ($item->productId !== $productId || $item->pricePerUnit !== $pricePerUnit || $item->weightFrom !== $weightFrom || $item->weightTo !== $weightTo) {
            return null;
        }

        return $previous->priceTotal / $item->amount;
    }

    /**
     * Whole turkeys: the given class, or the cold calendar class of the order week (halved for halves, as on the web).
     * Other products never carry a weight class.
     *
     * @return array{null|float, null|float}
     * @throws OrderItemInvalid
     */
    private function weights(int $key, EditOrderItem $item, AdminOrderProduct $product, \DateTimeImmutable $date): array
    {
        if (!$product->isTurkey()) {
            return [null, null];
        }

        if ($item->weightFrom !== null && $item->weightTo !== null) {
            return [$item->weightFrom, $item->weightTo];
        }

        $class = $this->getColdProductsCalendar->all()[(int) $date->format('o')][(int) $date->format('W')][(int) $product->turkeyTypeId()] ?? null;

        if ($class === null) {
            throw new OrderItemInvalid($key, sprintf('Pro týden %s není v kalendáři chlazených krůt váha, zadejte váhu od–do.', $date->format('W/o')));
        }

        return $product->isHalf() ? [$class->weightFrom / 2, $class->weightTo / 2] : [$class->weightFrom, $class->weightTo];
    }

    /**
     * @return array{BoxxiEditOutcome, bool}
     * @throws BoxxiRequestFailed
     */
    private function boxxi(AdminOrderRecord $before, AdminOrderPlace $place, EditOrder $message): array
    {
        if ($before->boxxiSyncedAt === null) {
            return [BoxxiEditOutcome::None, false];
        }

        $number = $before->boxxiShipmentNumber;

        if ($number === null) {
            return [BoxxiEditOutcome::Manual, false];
        }

        if (!$place->isCoolBalik) {
            $this->boxxiShipments->cancel($number);
            $this->repository->update('order', $message->orderId, ['boxxi_sync_dt' => null, 'boxxi_shipment_number' => null], $message->adminId);

            return [BoxxiEditOutcome::Cancelled, false];
        }

        return [BoxxiEditOutcome::NeedsUpdate, trim($before->order->note) !== $message->note];
    }

    /**
     * @param list<AdminOrderDetailItem> $beforeItems
     */
    private function notify(AdminOrderRecord $before, array $beforeItems, OrderPrice $price, int $orderId): bool
    {
        try {
            $this->changeMailer->send($before, $beforeItems, $this->getAdminOrders->oneById($orderId), $this->getAdminOrders->detailItems($orderId), $price);

            return true;
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Order {orderId} change email failed: {message}', ['orderId' => $orderId, 'message' => $exception->getMessage(), 'exception' => $exception]);

            return false;
        }
    }
}
