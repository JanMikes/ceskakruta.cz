<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Order;

use CeskaKruta\Web\Exceptions\PohodaRequestFailed;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Order\SendOrderToPohoda;
use CeskaKruta\Web\Query\Admin\GetAdminOrders;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use CeskaKruta\Web\Services\Pohoda\PohodaClient;
use CeskaKruta\Web\Services\Pohoda\PohodaOrderXmlFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Old `Pohoda::sendOrderRequest`: delivery places get a Pohoda address book entry (searched, else created) as partner,
 * then the order document(s) are sent. Duplicates are refused by Pohoda (`checkDuplicity`).
 */
#[AsMessageHandler]
readonly final class SendOrderToPohodaHandler
{
    public function __construct(
        private GetAdminOrders $getAdminOrders,
        private PohodaClient $pohodaClient,
        private PohodaOrderXmlFactory $xmlFactory,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     * @throws PohodaRequestFailed
     */
    public function __invoke(SendOrderToPohoda $message): void
    {
        $order = $this->getAdminOrders->oneById($message->orderId)->order;
        $addressId = null;

        if ($order->place->forceDeliveryAddress) {
            $addressId = $this->pohodaClient->findAddressId($order) ?? $this->pohodaClient->createAddress($order);
        }

        foreach ($this->xmlFactory->orderDocuments($order, $this->getAdminOrders->items($order->id), $addressId) as $xml) {
            $this->pohodaClient->send($xml);
        }

        $this->repository->update('order', $order->id, [
            'pohoda_sync_dt' => date('Y-m-d H:i:s'),
        ], $message->adminId);
    }
}
