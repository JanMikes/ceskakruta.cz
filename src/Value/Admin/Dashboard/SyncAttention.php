<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * Orders not yet sent to Pohoda / Boxxi (Boxxi only for CoolBalík places), split by expedition day: overdue (today or
 * earlier, since `$since`) and upcoming.
 */
final class SyncAttention
{
    public int $pohodaOverdue = 0;
    public int $pohodaUpcoming = 0;
    public int $boxxiOverdue = 0;
    public int $boxxiUpcoming = 0;

    public function __construct(
        readonly public \DateTimeImmutable $since,
    ) {
    }

    public function add(OrderGroup $group, bool $overdue): void
    {
        if ($overdue) {
            $this->pohodaOverdue += $group->pohodaMissingCount;
            $this->boxxiOverdue += $group->boxxiMissingCount;
        } else {
            $this->pohodaUpcoming += $group->pohodaMissingCount;
            $this->boxxiUpcoming += $group->boxxiMissingCount;
        }
    }

    public function isEmpty(): bool
    {
        return $this->pohodaOverdue + $this->pohodaUpcoming + $this->boxxiOverdue + $this->boxxiUpcoming === 0;
    }
}
