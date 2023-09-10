<?php
declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Entity\NewsletterSubscription;
use CeskaKruta\Web\Exceptions\EmailAlreadySubscribedToNewsletter;
use CeskaKruta\Web\Message\SubscribeNewsletter;
use CeskaKruta\Web\Repository\NewsletterSubscriptionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class SubscribeNewsletterHandler
{
    public function __construct(
        private NewsletterSubscriptionRepository $newsletterSubscriptionRepository,
    ) {
    }

    /**
     * @throws EmailAlreadySubscribedToNewsletter
     */
    public function __invoke(SubscribeNewsletter $command): void
    {
        if ($this->newsletterSubscriptionRepository->isAlreadySubscribed($command->email)) {
            throw new EmailAlreadySubscribedToNewsletter();
        }

        $subscription = new NewsletterSubscription(
            $command->email,
            new \DateTimeImmutable(),
        );

        $this->newsletterSubscriptionRepository->save($subscription);
    }
}
