<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\User;

use CeskaKruta\Web\FormData\Admin\UserFormData;

readonly final class EditUser
{
    public function __construct(
        public int $id,
        public int $adminId,
        public string $email,
        public null|string $name,
        public null|string $phone,
        public null|int $preferredPlaceId,
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
        public bool $allowWholesale,
        public bool $isCeskakruta,
        public bool $isAdmin,
    ) {
    }

    public static function fromFormData(int $id, int $adminId, UserFormData $formData): self
    {
        return new self(
            id: $id,
            adminId: $adminId,
            email: (string) $formData->email,
            name: $formData->name,
            phone: $formData->phone,
            preferredPlaceId: $formData->preferredPlaceId,
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
            allowWholesale: $formData->allowWholesale,
            isCeskakruta: $formData->isCeskakruta,
            isAdmin: $formData->isAdmin,
        );
    }

    /**
     * The password column is shared with the old app and never written here.
     *
     * @return array<string, null|scalar>
     */
    public function columns(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'phone' => $this->phone,
            'preferred_place_id' => $this->preferredPlaceId,
            'delivery_street' => $this->deliveryStreet,
            'delivery_city' => $this->deliveryCity,
            'delivery_zip' => $this->deliveryZip,
            'company_invoicing' => $this->companyInvoicing,
            'company_name' => $this->companyName,
            'company_id' => $this->companyId,
            'company_vat_id' => $this->companyVatId,
            'invoicing_street' => $this->invoicingStreet,
            'invoicing_city' => $this->invoicingCity,
            'invoicing_zip' => $this->invoicingZip,
            'allow_wholesale' => $this->allowWholesale,
            'is_ceskakruta' => $this->isCeskakruta,
            'is_admin' => $this->isAdmin,
        ];
    }
}
