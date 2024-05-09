<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class Price
{
    public function __construct(
        public int $amount,
    ) {
    }

    public function add(int $amount): self
    {
        return new Price($this->amount + $amount);
    }
}
