<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Vendor;

use CeskaKruta\Web\FormData\Admin\VendorFormData;

readonly final class CreateVendor
{
    public function __construct(
        public int $adminId,
        public null|string $place,
        public null|string $name,
        public null|string $address,
        public null|string $city,
        public null|string $zip,
        public null|string $email,
        public null|string $web,
        public null|string $phone,
    ) {
    }

    public static function fromFormData(int $adminId, VendorFormData $formData): self
    {
        return new self(
            adminId: $adminId,
            place: $formData->place,
            name: $formData->name,
            address: $formData->address,
            city: $formData->city,
            zip: $formData->zip,
            email: $formData->email,
            web: $formData->web,
            phone: $formData->phone,
        );
    }

    /**
     * @return array<string, null|string>
     */
    public function columns(): array
    {
        return [
            'place' => $this->place,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'zip' => $this->zip,
            'email' => $this->email,
            'web' => $this->web,
            'phone' => $this->phone,
        ];
    }
}
