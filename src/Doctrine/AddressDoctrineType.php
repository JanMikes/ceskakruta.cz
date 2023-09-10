<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

final class AddressDoctrineType extends JsonType
{
    public const NAME = 'address';

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
}
