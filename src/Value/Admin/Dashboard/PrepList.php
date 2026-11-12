<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * What to prepare for one day: whole + half turkeys ("Celé kusy + půlky") per type, everything else separately.
 */
readonly final class PrepList
{
    /**
     * @param list<TurkeyPrepGroup> $turkeys
     * @param list<PrepItem> $others sorted by name
     */
    public function __construct(
        public array $turkeys,
        public array $others,
    ) {
    }

    /**
     * @param list<PrepItem> $items
     */
    public static function fromItems(array $items): self
    {
        $turkeys = [];
        $others = [];

        foreach ($items as $item) {
            if ($item->isTurkey()) {
                $turkeys[(int) $item->type][] = $item;
            } else {
                $others[] = $item;
            }
        }

        // Whole product followed by its half (pair = whole id), then by weight class
        $turkeyOrder = static fn (PrepItem $a, PrepItem $b): int => [$a->halfOfProductId ?? $a->productId, $a->isHalf(), $a->weightFrom, $a->weightTo]
            <=> [$b->halfOfProductId ?? $b->productId, $b->isHalf(), $b->weightFrom, $b->weightTo];
        usort($others, static fn (PrepItem $a, PrepItem $b): int => strcoll($a->name, $b->name));

        $groups = [];

        foreach (PrepItem::TURKEY_TYPES as $type => $label) {
            if (isset($turkeys[$type])) {
                usort($turkeys[$type], $turkeyOrder);
                $groups[] = new TurkeyPrepGroup($type, $label, $turkeys[$type]);
            }
        }

        return new self($groups, $others);
    }

    public function isEmpty(): bool
    {
        return $this->turkeys === [] && $this->others === [];
    }
}
