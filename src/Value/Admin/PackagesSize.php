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
        $sizes = [];

        foreach (explode(',', $input) as $item) {
            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $size = (int) $item;

            if (preg_match('/^[1-9]\d{0,8}$/', $item) !== 1 || $size < 1) {
                throw new InvalidPackagesSize(sprintf('Velikost balení "%s" musí být kladné celé číslo (gramy).', $item));
            }

            $sizes[] = $size;
        }

        return self::normalized($sizes);
    }

    /**
     * Reads the value stored in `product.packages_size` (e.g. `[500,1000]`, `[500, 1000]`, `[]`).
     */
    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);
        $sizes = [];

        foreach (is_array($decoded) ? $decoded : [] as $size) {
            if (is_numeric($size) && (int) $size > 0) {
                $sizes[] = (int) $size;
            }
        }

        return self::normalized($sizes);
    }

    /**
     * Value for `product.packages_size`: `[500,1000,1500]`, empty → `[]`.
     */
    public function toJson(): string
    {
        return json_encode($this->sizes, JSON_THROW_ON_ERROR);
    }

    /**
     * Value for the admin form field: "500, 1000, 1500", empty → "".
     */
    public function toInput(): string
    {
        return implode(', ', $this->sizes);
    }

    /**
     * @param array<positive-int> $sizes
     */
    private static function normalized(array $sizes): self
    {
        $sizes = array_values(array_unique($sizes));
        sort($sizes);

        return new self($sizes);
    }
}
