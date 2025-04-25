<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\UnsupportedDeliveryToPostalCode;
use CeskaKruta\Web\Message\DetectUserDeliveryPlace;
use CeskaKruta\Web\Message\EditUserInfo;
use CeskaKruta\Web\Services\CeskaKrutaDelivery;
use CeskaKruta\Web\Services\CoolBalikDelivery;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Services\UserService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class DetectUserDeliveryPlaceHandler
{
    public function __construct(
        private UserService $userService,
        private UserProvider $userProvider,
        private CeskaKrutaDelivery $ceskaKrutaDelivery,
        private CoolBalikDelivery $coolBalikDelivery,
    ) {
    }

    /**
     * @throws UnsupportedDeliveryToPostalCode
     */
    public function __invoke(DetectUserDeliveryPlace $message): void
    {
        $email = $this->userService->getEmailById($message->userId);
        $user = $this->userProvider->loadUserByIdentifier($email);

        if ($user->deliveryZip === null) {
            throw new UnsupportedDeliveryToPostalCode();
        }

        $placeId = null;

        if ($this->ceskaKrutaDelivery->canDeliverToPostalCode($user->deliveryZip)) {
            $placeId = $this->ceskaKrutaDelivery::DELIVERY_PLACE_ID;
        }

        if ($this->coolBalikDelivery->canDeliverToPostalCode($user->deliveryZip)) {
            $placeId = $this->coolBalikDelivery::DELIVERY_PLACE_ID;
        }

        if ($placeId === null) {
            throw new UnsupportedDeliveryToPostalCode();
        }

        $this->userService->update(new EditUserInfo(
            email: $email,
            name: $user->name,
            preferredPlaceId: $placeId,
            phone: $user->phone,
            deliveryStreet: $user->deliveryStreet,
            deliveryCity: $user->deliveryCity,
            deliveryZip: $user->deliveryZip,
            companyInvoicing: $user->companyInvoicing,
            companyName: $user->companyName,
            companyId: $user->companyId,
            invoicingStreet: $user->invoicingStreet,
            invoicingCity: $user->invoicingCity,
            invoicingZip: $user->invoicingZip,
        ));
    }
}
