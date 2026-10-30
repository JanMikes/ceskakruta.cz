<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * Which day an order belongs to in the operations part of the dashboard.
 */
enum DateBasis: string
{
    /** Day the order must be prepared and leave = Pohoda `dateTo` (`PohodaDates::dateTo`) */
    case Expedition = 'expedition';
    /** Day the customer picks the order up / gets it delivered = `order.date` */
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Expedition => 'Expedice',
            self::Customer => 'Vyzvednutí / doručení',
        };
    }
}
