<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Repository;

use Doctrine\ORM\EntityManagerInterface;
use CeskaKruta\Web\Entity\ProductVariant;

readonly final class ProductVariantRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return list<ProductVariant>
     */
    public function findAll(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('product_variant')
            ->from(ProductVariant::class, 'product_variant')
            ->join('product_variant.product', 'product')
            ->addSelect('product')
            ->getQuery()
            ->getResult();
    }
}
