<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class NewsletterBatchResult
{
    /**
     * @param list<int> $sent queue row ids
     * @param null|string $error delivery failure that stopped the batch, the rows sent before it stay sent
     */
    public function __construct(
        public int $queued,
        public array $sent,
        public null|string $error = null,
    ) {
    }
}
