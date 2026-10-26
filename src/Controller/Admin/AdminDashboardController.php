<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminDashboardController extends AbstractController
{
    #[Route(path: '/admin', name: 'admin_dashboard')]
    public function __invoke(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }
}
