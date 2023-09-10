<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use CeskaKruta\Web\Entity\ProductVariant;
use Ramsey\Uuid\UuidInterface;

readonly final class GetVariants
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<UuidInterface> $variantIds
     * @return list<ProductVariant>
     */
    public function byIds(array $variantIds): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('product_variant')
            ->from(ProductVariant::class, 'product_variant')
            ->join('product_variant.product', 'product')
            ->where('product_variant.id IN (:variantIds)')
            ->setParameter('variantIds', $variantIds, ArrayParameterType::STRING)
            ->addSelect('product')
            ->getQuery()
            ->getResult();
    }
}
