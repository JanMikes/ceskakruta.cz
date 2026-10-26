<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

use Symfony\Component\HttpFoundation\InputBag;

/**
 * Order list filter = plain GET params `date_from`, `date_to` (YYYY-MM-DD, on `order.date`) and `customer_name` (LIKE).
 */
readonly final class AdminOrderFilter
{
    public function __construct(
        public null|\DateTimeImmutable $dateFrom = null,
        public null|\DateTimeImmutable $dateTo = null,
        public null|string $customerName = null,
    ) {
    }

    /**
     * @param InputBag<string> $query
     */
    public static function fromQuery(InputBag $query): self
    {
        $customerName = trim((string) $query->get('customer_name', ''));

        return new self(
            self::date((string) $query->get('date_from', '')),
            self::date((string) $query->get('date_to', '')),
            $customerName !== '' ? $customerName : null,
        );
    }

    /**
     * Query params of the active filter (for pagination and export links).
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'date_from' => $this->dateFrom?->format('Y-m-d'),
            'date_to' => $this->dateTo?->format('Y-m-d'),
            'customer_name' => $this->customerName,
        ], static fn (null|string $value): bool => $value !== null);
    }

    private static function date(string $value): null|\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }
}
