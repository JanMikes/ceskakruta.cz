<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Services\NewsletterSubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UnsubscribeFromNewsletterController extends AbstractController
{
    public function __construct(
        readonly private NewsletterSubscriptionService $newsletterSubscriptionService,
    ) {}

    #[Route(path: '/odhlasit-newsletter/{email}/{hash}', name: 'unsubscribe_newsletter')]
    public function __invoke(string $email, string $hash): Response
    {
        if ($this->newsletterSubscriptionService->isUnsubscribeHashValid($email, $hash)) {
            if ($this->newsletterSubscriptionService->unsubscribe($email) > 0) {
                $this->addFlash('success', 'Byli jste úspěšně odhlášeni z odběru novinek!');
            } else {
                $this->addFlash('info', 'Váš e-mail se již v naší databázi nenachází!');
            }
        } else {
            $this->addFlash('danger', 'Omlouváme se, byl zadán chybný odkaz!');
        }

        return $this->redirectToRoute('homepage');
    }
}
