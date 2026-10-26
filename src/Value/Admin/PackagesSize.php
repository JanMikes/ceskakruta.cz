<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

use CeskaKruta\Web\Exceptions\InvalidPackagesSize;

/**
 * `product.packages_size` — package sizes in grams, stored as a JSON int array (e.g. `[500,1000]`, empty `[]`),
 * read by the frontend `GetProducts` via json_decode. Admin input is a comma separated list "500, 1000" (PLAN §2.9).
 */
final readonly class PackagesSize
{
    /**
     * @param list<positive-int> $sizes Deduplicated, sorted ascending
     */
    private function __construct(
        public array $sizes,
    ) {
    }

    /**
     * Parses "1000, 500, 500" → [500, 1000]; empty / whitespace-only input → [].
     *
     * @throws InvalidPackagesSize when any item is not a positive integer
     */
    public static function fromInput(string $input): self
    {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * Reads the value stored in `product.packages_size` (e.g. `[500,1000]`, `[500, 1000]`, `[]`).
     */
    public static function fromJson(string $json): self
    {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * Value for `product.packages_size`: `[500,1000,1500]`, empty → `[]`.
     */
    public function toJson(): string
    {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * Value for the admin form field: "500, 1000, 1500", empty → "".
     */
    public function toInput(): string
    {
        throw new \LogicException('Not implemented yet');
    }
}
