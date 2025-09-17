<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\RepeatOrder;
use CeskaKruta\Web\Value\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class RepeatOrderController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/uzivatel/objednavky/{orderId}/zopakovat', name: 'user_repeat_order', methods: ['POST'])]
    public function __invoke(#[CurrentUser] User $loggedUser, int $orderId): Response
    {
        try {
            $this->bus->dispatch(
                new RepeatOrder(
                    $loggedUser->id,
                    $orderId,
                ),
            );

            $this->addFlash('success', 'Objednávka byla úspěšně zkopírována do košíku.');
        } catch (\Exception $e) {
            $this->logger->error('Failed to repeat order', ['exception' => $e, 'orderId' => $orderId, 'userId' => $loggedUser->id]);

            $this->addFlash('danger', 'Při kopírování objednávky došlo k chybě.');
        }

        return $this->redirectToRoute('cart');
    }
}