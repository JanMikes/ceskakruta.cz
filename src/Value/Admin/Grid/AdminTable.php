<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Grid;

/**
 * Whitelist of tables the generic grid actions may touch, with the per-table rules of the old lists
 * (docs/admin/PLAN.md §2, §3.3). The value is the DB table name and the URL segment.
 */
enum AdminTable: string
{
    case Actuality = 'actuality';
    case CalendarDead = 'calendar_dead';
    case CalendarCold = 'calendar_cold';
    case CalendarAlive = 'calendar_alive';
    case Product = 'product';
    case Recipe = 'recipe';
    case Sortiment = 'sortiment';
    case Vendor = 'vendor';
    case Newsletter = 'newsletter';
    case Place = 'place';
    case PlaceClosedDay = 'place_closed_day';

    /**
     * Whole turkey products (type 3, 4) are always offered, they can not be deactivated nor deleted.
     */
    private const PROTECTED_PRODUCT_TYPES = [3, 4];

    /**
     * Sortiment rows the frontend relies on.
     */
    private const PROTECTED_SORTIMENT_IDS = [1, 2];

    public function isToggleable(): bool
    {
        return match ($this) {
            self::Newsletter, self::PlaceClosedDay => false,
            default => true,
        };
    }

    public function isOrderable(): bool
    {
        return match ($this) {
            self::Product, self::Sortiment, self::Vendor, self::Place => true,
            default => false,
        };
    }

    public function isDeletable(): bool
    {
        return $this !== self::Place;
    }

    public function supportsActivateAll(): bool
    {
        return $this->isToggleable();
    }

    /**
     * Column limiting reorder and "activate all" to the current list (recipes of a product, sortiment of a category).
     */
    public function scopeColumn(): null|string
    {
        return match ($this) {
            self::Recipe => 'product_id',
            self::Sortiment => 'category_id',
            default => null,
        };
    }

    /**
     * Prefix of the row DOM id, used as the anchor after an action.
     */
    public function rowAnchorPrefix(): string
    {
        return $this === self::PlaceClosedDay ? 'closed-day-' : 'item-';
    }

    public function supports(GridAction $action): bool
    {
        return match ($action) {
            GridAction::Activate, GridAction::Deactivate => $this->isToggleable(),
            GridAction::Delete => $this->isDeletable(),
            GridAction::MoveUp, GridAction::MoveDown => $this->isOrderable(),
        };
    }

    /**
     * Table-level support plus the row rules (hidden actions in the old lists).
     *
     * @param array<string, mixed> $row
     */
    public function allows(GridAction $action, array $row): bool
    {
        if (!$this->supports($action)) {
            return false;
        }

        if ($this === self::Product && in_array($action, [GridAction::Activate, GridAction::Deactivate, GridAction::Delete], true)) {
            return !in_array((int) self::intValue($row, 'type'), self::PROTECTED_PRODUCT_TYPES, true);
        }

        if ($this === self::Sortiment && $action === GridAction::Delete) {
            return !in_array(self::intValue($row, 'id'), self::PROTECTED_SORTIMENT_IDS, true);
        }

        return true;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function intValue(array $row, string $column): null|int
    {
        $value = $row[$column] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }
}
