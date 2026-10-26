<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Grid;

/**
 * Row actions of the generic grid, the value is the URL segment (docs/admin/PLAN.md §4.6).
 */
enum GridAction: string
{
    case Activate = 'activate';
    case Deactivate = 'deactivate';
    case Delete = 'delete';
    case MoveUp = 'move-up';
    case MoveDown = 'move-down';

    public function successMessage(): string
    {
        return match ($this) {
            self::Activate => 'Záznam úspěšně aktivován',
            self::Deactivate => 'Záznam úspěšně deaktivován',
            self::Delete => 'Záznam úspěšně smazán',
            self::MoveUp, self::MoveDown => 'Pořadí bylo úspěšně změněno!',
        };
    }
}
