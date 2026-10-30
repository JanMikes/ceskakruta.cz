<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

use Symfony\Component\HttpFoundation\InputBag;

/**
 * User list filter = plain GET params `search` (LIKE on email, name, phone, company name)
 * and `wholesale` (`1` allowed / `0` not allowed, anything else = all).
 */
readonly final class AdminUserFilter
{
    public function __construct(
        public null|string $search = null,
        public null|bool $wholesale = null,
    ) {
    }

    /**
     * @param InputBag<string> $query
     */
    public static function fromQuery(InputBag $query): self
    {
        $search = trim((string) $query->get('search', ''));
        $wholesale = (string) $query->get('wholesale', '');

        return new self(
            $search !== '' ? $search : null,
            match ($wholesale) {
                '1' => true,
                '0' => false,
                default => null,
            },
        );
    }

    /**
     * Query params of the active filter (for pagination links).
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'search' => $this->search,
            'wholesale' => $this->wholesale !== null ? (string) (int) $this->wholesale : null,
        ], static fn (null|string $value): bool => $value !== null);
    }
}
