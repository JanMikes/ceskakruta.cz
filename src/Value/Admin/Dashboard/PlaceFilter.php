<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * Which places the dashboard operations show: all, own shops ("Vlastní prodejny") or one place.
 * Raw value = live prop / query param `place`: '' (all), `own` or a place id; anything else = all.
 */
readonly final class PlaceFilter
{
    public const ALL = '';
    public const OWN_SHOPS = 'own';

    /**
     * Česká krůta's own shops (Rychvald, Ostrava, Frýdlant, Přerov, Havířov). There is no `place` flag for it and the
     * ids never change; Karviná (6) is a partner pickup point and 22 a wholesale delivery, both left out on purpose.
     */
    public const OWN_SHOP_PLACE_IDS = [1, 2, 3, 4, 5];

    /**
     * @param null|list<int> $placeIds null = all places
     */
    private function __construct(
        public string $value,
        private null|array $placeIds,
    ) {
    }

    public static function all(): self
    {
        return new self(self::ALL, null);
    }

    public static function fromValue(string $value): self
    {
        if ($value === self::OWN_SHOPS) {
            return new self(self::OWN_SHOPS, self::OWN_SHOP_PLACE_IDS);
        }

        if (preg_match('/^[1-9]\d{0,9}$/', $value) === 1) {
            return new self($value, [(int) $value]);
        }

        return self::all();
    }

    public function isAll(): bool
    {
        return $this->placeIds === null;
    }

    public function matches(int $placeId): bool
    {
        return $this->placeIds === null || in_array($placeId, $this->placeIds, true);
    }
}
