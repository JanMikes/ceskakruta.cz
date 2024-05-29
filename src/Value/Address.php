<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class Address
{
    public string $postalCode;

    public function __construct(
        public string $street,
        public string $city,
        string $postalCode,
    ) {
        $this->postalCode = str_replace(' ', '', $postalCode);
    }

    /**
     * @return array{street: string, city: string, postalCode: string}
     */
    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
            'postalCode' => $this->postalCode,
        ];
    }

    /**
     * @param array{street: string, city: string, postalCode: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            street: $data['street'],
            city: $data['city'],
            postalCode: $data['postalCode'],
        );
    }
}
