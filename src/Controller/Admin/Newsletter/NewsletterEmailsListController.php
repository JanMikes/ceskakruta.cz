<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Newsletter;

use CeskaKruta\Web\Query\Admin\GetAdminNewsletterEmails;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NewsletterEmailsListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminNewsletterEmails $getAdminNewsletterEmails,
    ) {
    }

    #[Route(path: '/admin/emaily', name: 'admin_newsletter_emails')]
    public function __invoke(): Response
    {
        return $this->render('admin/newsletter/emails.html.twig', [
            'emails' => $this->getAdminNewsletterEmails->all(),
        ]);
    }
}
