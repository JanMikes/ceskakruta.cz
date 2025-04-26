<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class CompanyBillingInfo
{
    public function __construct(
        public string $companyName,
        public string $companyId,
        public string $companyVatId,
        public Address $address,
    ) {
    }
}
