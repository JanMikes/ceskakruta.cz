<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\UnskipRecurringOrder;
use CeskaKruta\Web\Repository\RecurringOrderSkipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class UnskipRecurringOrderHandler
{
    public function __construct(
        private RecurringOrderSkipRepository $recurringOrderSkipRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(UnskipRecurringOrder $message): void
    {
        $skip = $this->recurringOrderSkipRepository->findActiveSkip(
            $message->userId,
            $message->dayOfWeek
        );

        if ($skip !== null) {
            $this->recurringOrderSkipRepository->remove($skip);
            $this->entityManager->flush();
        }
    }
}