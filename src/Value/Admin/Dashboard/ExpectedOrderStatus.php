<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

enum ExpectedOrderStatus: string
{
    /** Order of the customer for the day exists (created by the cron or manually) */
    case Created = 'created';
    /** Customer skipped this delivery (`recurring_order_skip`) */
    case Skipped = 'skipped';
    /** Ordering deadline not reached yet, the cron creates the order after it */
    case Waiting = 'waiting';
    /** Deadline just passed, the cron should create the order within the next run */
    case Processing = 'processing';
    /** Deadline passed and there is no order – needs attention */
    case Missing = 'missing';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Vytvořena',
            self::Skipped => 'Vynechána',
            self::Waiting => 'Čeká na uzávěrku',
            self::Processing => 'Vytváří se',
            self::Missing => 'Chybí',
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::Created => 'is-success',
            self::Skipped => 'is-muted',
            self::Waiting, self::Processing => 'is-info',
            self::Missing => 'is-danger',
        };
    }
}
