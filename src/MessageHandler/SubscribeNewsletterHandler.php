<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\EmailAlreadySubscribedToNewsletter;
use CeskaKruta\Web\Message\SubscribeNewsletter;
use CeskaKruta\Web\Services\NewsletterSubscriptionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class SubscribeNewsletterHandler
{
    public function __construct(
        private NewsletterSubscriptionService $newsletterSubscriptionService,
    ) {
    }

    /**
     * @throws EmailAlreadySubscribedToNewsletter
     */
    public function __invoke(SubscribeNewsletter $message): void
    {
        if ($this->newsletterSubscriptionService->isSubscribed($message->email) === true) {
            throw new EmailAlreadySubscribedToNewsletter();
        }

        $this->newsletterSubscriptionService->subscribe($message->email);
    }
}
