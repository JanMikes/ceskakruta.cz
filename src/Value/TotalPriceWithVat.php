<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class TotalPriceWithVat
{
    public function __construct(
        public int $amount,
        public Currency $currency,
    ) {
    }

    public function add(int $amount): self
    {
        return new TotalPriceWithVat($this->amount + $amount, $this->currency);
    }
}
