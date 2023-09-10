<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Repository;

use Doctrine\ORM\EntityManagerInterface;
use CeskaKruta\Web\Entity\NewsletterSubscription;
use CeskaKruta\Web\Exceptions\ProductNotFound;

readonly final class NewsletterSubscriptionRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @throws ProductNotFound
     */
    public function save(NewsletterSubscription $newsletterSubscription): void
    {
        $this->entityManager->persist($newsletterSubscription);
        $this->entityManager->flush();
    }

    public function isAlreadySubscribed(string $email): bool
    {
        $qb = $this->entityManager->createQueryBuilder();
        $count = $qb->select('COUNT(newsletter_subscription)')
            ->from(NewsletterSubscription::class, 'newsletter_subscription')
            ->where('newsletter_subscription.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
