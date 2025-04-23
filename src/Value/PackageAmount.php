<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

/**
 * @phpstan-type PackageAmountArray array{sizeG: float, amount: int}
 */
readonly final class PackageAmount
{
    public function __construct(
        public float $sizeG,
        public int $amount,
    ) {
    }

    /**
     * @return PackageAmountArray
     */
    public function toArray(): array
    {
        return [
            'sizeG' => $this->sizeG,
            'amount' => $this->amount,
        ];
    }

    /**
     * @param PackageAmountArray $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sizeG: $data['sizeG'],
            amount: $data['amount'],
        );
    }

    /**
     * @return array<self>
     */
    public static function createCollectionFromJson(string $json): array
    {
        /** @var array<PackageAmountArray> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $collection = [];

        foreach ($data as $inputData) {
            $collection[] = self::fromArray($inputData);
        }

        return $collection;
    }
}
