<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Message;

use Ramsey\Uuid\UuidInterface;

readonly final class AddItemToCart
{
    public function __construct(
        public UuidInterface $productVariantId,
    ) {}
}
