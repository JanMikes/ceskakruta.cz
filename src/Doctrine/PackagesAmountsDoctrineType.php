<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Doctrine;

use CeskaKruta\Web\Value\PackageAmount;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\JsonType;

/**
 * @phpstan-import-type PackageAmountArray from PackageAmount
 */
final class PackagesAmountsDoctrineType extends JsonType
{
    public const NAME = 'package_amount[]';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'TEXT';
    }

    /**
     * @return null|array<PackageAmount>
     *
     * @throws ConversionException
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): null|array
    {
        if ($value === null) {
            return null;
        }

        $jsonData = parent::convertToPHPValue($value, $platform);
        assert(is_array($jsonData));

        $inputs = [];

        foreach ($jsonData as $data) {
            /** @var PackageAmountArray $data */
            $inputs[] = PackageAmount::fromArray($data);
        }

        return $inputs;
    }

    /**
     * @param null|array<PackageAmount> $value
     * @throws ConversionException
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        $data = [];

        foreach ($value as $input) {
            if (!is_a($input, PackageAmount::class)) {
                throw InvalidType::new($value, self::NAME, [PackageAmount::class]);
            }

            $data[] = $input->toArray();
        }

        return parent::convertToDatabaseValue($data, $platform);
    }
}
