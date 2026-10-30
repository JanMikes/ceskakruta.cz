<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * How an order reaches the customer, derived from the `place` flags of the order.
 */
enum SalesChannel: string
{
    /** Shop (prodejna / výdejní místo), the customer picks the order up */
    case Pickup = 'pickup';
    /** Own delivery routes ("Rozvoz domů …", "Rozvoz Česká Krůta") */
    case OwnDelivery = 'delivery';
    /** Shipped by CoolBalík (Boxxi), expedited before the delivery day */
    case CoolBalik = 'coolbalik';
    /** Wholesale-only place (`allow_only_wholesale`, "Velkoodběratel") */
    case Wholesale = 'wholesale';

    public static function fromPlaceFlags(bool $isWholesale, bool $isCoolBalik, bool $forceDeliveryAddress): self
    {
        return match (true) {
            $isWholesale => self::Wholesale,
            $isCoolBalik => self::CoolBalik,
            $forceDeliveryAddress => self::OwnDelivery,
            default => self::Pickup,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Prodejny',
            self::OwnDelivery => 'Vlastní rozvoz',
            self::CoolBalik => 'CoolBalík',
            self::Wholesale => 'Velkoodběratelé',
        };
    }

    /**
     * Chart colour, validated categorical palette (distinct in hue and lightness, colour-blind safe order).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pickup => '#2a78b8',
            self::OwnDelivery => '#e08a1e',
            self::CoolBalik => '#2f9e77',
            self::Wholesale => '#8a5cc2',
        };
    }
}
