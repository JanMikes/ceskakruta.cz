<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\EditUserInfo;
use CeskaKruta\Web\Message\LoadBillingInfoFromAres;
use CeskaKruta\Web\Services\UserService;
use h4kuna\Ares\Ares;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class LoadBillingInfoFromAresHandler
{
    public function __construct(
        private UserService $userService,
        private Ares $ares,
    ) {
    }

    public function __invoke(LoadBillingInfoFromAres $message): void
    {
        $exception = null;
        $companyName = $message->companyName;
        $companyVatId = $message->companyVatId;
        $invoicingStreet = $message->invoicingStreet;
        $invoicingCity = $message->invoicingCity;
        $invoicingZip = $message->invoicingZip;

        if ($message->companyId !== null) {
            try {
                $aresInfo = $this->ares->loadBasic($message->companyId);

                $companyName = $aresInfo->company;
                $companyVatId = $aresInfo->tin;
                $invoicingStreet = $aresInfo->street . ' ' . $aresInfo->house_number;
                $invoicingZip = $aresInfo->zip;
                $invoicingCity = sprintf(
                    '%s%s%s',
                    $aresInfo->city_post,
                    trim((string) $aresInfo->city_district) !== '' ? ' - ' : '',
                    trim((string) $aresInfo->city_district),
                );
            } catch (\Throwable $exception) {
            }
        }

        $message = new EditUserInfo(
            email: $message->email,
            name: $message->name,
            preferredPlaceId: $message->preferredPlaceId,
            phone: $message->phone,
            deliveryStreet: $message->deliveryStreet,
            deliveryCity: $message->deliveryCity,
            deliveryZip: $message->deliveryZip,
            companyInvoicing: $message->companyInvoicing,
            companyName: $companyName,
            companyId: $message->companyId,
            companyVatId: $companyVatId,
            invoicingStreet: $invoicingStreet,
            invoicingCity: $invoicingCity,
            invoicingZip: $invoicingZip,
        );

        $this->userService->update($message);

        if ($exception !== null) {
            throw $exception;
        }
    }
}
