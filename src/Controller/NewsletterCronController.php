<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\Admin\Newsletter\SendNewsletterBatch;
use CeskaKruta\Web\Value\Admin\NewsletterBatchResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Newsletter cron fired by curl (PLAN §3.8), public route protected by CRON_TOKEN only.
 */
final class NewsletterCronController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        #[Autowire(env: 'CRON_TOKEN')]
        readonly private string $cronToken,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/cron/send-email', name: 'cron_newsletter_send', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $token = $request->query->all()['token'] ?? null;

        if ($this->cronToken === '' || !is_string($token) || !hash_equals($this->cronToken, $token)) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $result = $this->handle(new SendNewsletterBatch());
        assert($result instanceof NewsletterBatchResult);

        if ($result->error !== null) {
            return $this->json([
                'queued' => $result->queued,
                'sent' => $result->sent,
                'error' => $result->error,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'queued' => $result->queued,
            'sent' => $result->sent,
        ]);
    }
}
