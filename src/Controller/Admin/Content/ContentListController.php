<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Content;

use CeskaKruta\Web\Query\Admin\GetAdminContents;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContentListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminContents $getAdminContents,
    ) {
    }

    #[Route(path: '/admin/texty', name: 'admin_contents')]
    public function __invoke(): Response
    {
        return $this->render('admin/content/list.html.twig', [
            'contents' => $this->getAdminContents->all(),
        ]);
    }
}
