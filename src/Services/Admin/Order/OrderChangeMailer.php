<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Admin\Order;

use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Value\Admin\AdminOrderDetailItem;
use CeskaKruta\Web\Value\Admin\AdminOrderRecord;
use CeskaKruta\Web\Value\Admin\Order\OrderPrice;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * "Změna objednávky" email to the customer after an admin edit: what changed (before → after) + the order as it is now,
 * styled like the order confirmation. Prices are hidden for recurring orders, as in their confirmation.
 */
readonly final class OrderChangeMailer
{
    public const RECURRING_ORDER_SOURCE = 'Opakovaná objednávka';

    public function __construct(
        private MailerInterface $mailer,
        private GetPlaces $getPlaces,
    ) {
    }

    /**
     * @param list<AdminOrderDetailItem> $beforeItems
     * @param list<AdminOrderDetailItem> $afterItems
     * @throws TransportExceptionInterface
     */
    public function send(AdminOrderRecord $before, array $beforeItems, AdminOrderRecord $after, array $afterItems, OrderPrice $price): void
    {
        $showPrices = $after->source !== self::RECURRING_ORDER_SOURCE;
        $order = $after->order;

        $email = (new TemplatedEmail())
            ->from('objednavky@ceskakruta.cz')
            ->to($order->email)
            ->subject('Změna objednávky č. ' . $order->id)
            ->htmlTemplate('emails/user_order_changed.html.twig')
            ->context([
                'record' => $after,
                'order' => $order,
                'place' => $this->getPlaces->all()[$order->place->id] ?? null,
                'turkey_items' => array_values(array_filter($afterItems, static fn (AdminOrderDetailItem $item): bool => $item->item->productTypeId !== null)),
                'other_items' => array_values(array_filter($afterItems, static fn (AdminOrderDetailItem $item): bool => $item->item->productTypeId === null)),
                'changes' => OrderChangeList::between($before, $beforeItems, $after, $afterItems, $showPrices),
                'price' => $price,
                'show_prices' => $showPrices,
            ]);

        $email->getHeaders()->addTextHeader('X-Transport', 'orders');

        $this->mailer->send($email);
    }
}
