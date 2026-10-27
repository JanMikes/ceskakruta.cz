<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Newsletter;

use CeskaKruta\Web\Query\Admin\GetAdminNewsletterEmails;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NewsletterEmailsListController extends AbstractController
{
    private const PER_PAGE = 50;

    public function __construct(
        readonly private GetAdminNewsletterEmails $getAdminNewsletterEmails,
    ) {
    }

    #[Route(path: '/admin/emaily', name: 'admin_newsletter_emails')]
    public function __invoke(Request $request): Response
    {
        $total = $this->getAdminNewsletterEmails->count();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, $request->query->getInt('page', 1)));

        return $this->render('admin/newsletter/emails.html.twig', [
            'emails' => $this->getAdminNewsletterEmails->page(($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ]);
    }
}
