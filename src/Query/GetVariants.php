<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use CeskaKruta\Web\Entity\ProductVariant;
use Ramsey\Uuid\UuidInterface;

readonly final class GetVariants
{
    /**
     * @param list<UuidInterface> $variantIds
     * @return list<ProductVariant>
     */
    public function byIds(array $variantIds): array
    {
        return [];
    }
}
