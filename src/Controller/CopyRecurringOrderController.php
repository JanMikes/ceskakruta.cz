<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\CopyRecurringOrderToDay;
use CeskaKruta\Web\Value\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CopyRecurringOrderController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/uzivatel/kopirovat-pravidelnou-objednavku', name: 'user_copy_recurring_order', methods: ['POST'])]
    public function __invoke(#[CurrentUser] User $loggedUser, Request $request): Response
    {
        $sourceDay = (int) $request->request->get('source_day');
        $targetDay = (int) $request->request->get('target_day');

        try {
            $this->bus->dispatch(
                new CopyRecurringOrderToDay(
                    $loggedUser->id,
                    $sourceDay,
                    $targetDay,
                ),
            );

            $this->addFlash('success', 'Pravidelná objednávka byla úspěšně zkopírována.');
        } catch (\Exception $e) {
            $this->logger->error('Failed to copy recurring order', ['exception' => $e]);

            $this->addFlash('danger', 'Při kopírování pravidelné objednávky došlo k chybě.');
        }

        return $this->redirectToRoute('user_recurring_order', ['day' => $sourceDay]);
    }
}
