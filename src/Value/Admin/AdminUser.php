<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminUser
{
    public function __construct(
        public int $id,
        public null|string $email,
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
}
