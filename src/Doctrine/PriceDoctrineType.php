<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use CeskaKruta\Web\Value\Currency;
use CeskaKruta\Web\Value\Price;

final class PriceDoctrineType extends JsonType
{
    public const NAME = 'price';

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function canRequireSQLConversion(): bool
    {
        return true;
    }

    /**
     * @throws ConversionException
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Price|null
    {
        if ($value === null || $value === '[]' || $value === '{}') {
            return null;
        }

        /** @var array{value_without_vat: int, vat: int, currency: string} $jsonData */
        $jsonData = parent::convertToPHPValue($value, $platform);

        return new Price(
            $jsonData['value_without_vat'],
            $jsonData['vat'],
            Currency::from($jsonData['currency']),
        );
    }

    /**
     * @template T of null|Price
     * @param T $value
     * @throws ConversionException
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_a($value, Price::class)) {
            throw ConversionException::conversionFailedInvalidType($value, $this->getName(), [Price::class]);
        }

        $data = [
            'value_without_vat' => $value->valueWithoutVat,
            'vat' => $value->vat,
            'currency' => $value->currency->value,
        ];

        return parent::convertToDatabaseValue($data, $platform);
    }
}
