<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

use CeskaKruta\Web\FormData\UserInfoFormData;

readonly final class LoadBillingInfoFromAres
{
    public function __construct(
        public string $email,
        public null|string $name,
        public null|int $preferredPlaceId,
        public null|string $phone,
        public null|string $deliveryStreet,
        public null|string $deliveryCity,
        public null|string $deliveryZip,
        public bool $companyInvoicing,
        public null|string $companyName,
        public null|string $companyId,
        public null|string $companyVatId,
        public null|string $invoicingStreet,
        public null|string $invoicingCity,
        public null|string $invoicingZip,
    ) {
    }

    public static function fromFormData(string $email, UserInfoFormData $formData): self
    {
        return new self(
            email: $email,
            name: $formData->name,
            preferredPlaceId: $formData->preferredPlaceId,
            phone: $formData->phone,
            deliveryStreet: $formData->deliveryStreet,
            deliveryCity: $formData->deliveryCity,
            deliveryZip: $formData->deliveryZip,
            companyInvoicing: $formData->companyInvoicing,
            companyName: $formData->companyName,
            companyId: $formData->companyId,
            companyVatId: $formData->companyVatId,
            invoicingStreet: $formData->invoicingStreet,
            invoicingCity: $formData->invoicingCity,
            invoicingZip: $formData->invoicingZip,
        );
    }
}
