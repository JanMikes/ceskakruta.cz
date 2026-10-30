<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Daily operations: orders per day, what to prepare and the day's orders – all in the `Admin:DashboardOperations`
 * live component (selected day / basis / month in the query string).
 */
final class AdminPreparationController extends AbstractController
{
    #[Route(path: '/admin/co-pripravit', name: 'admin_preparation')]
    public function __invoke(): Response
    {
        return $this->render('admin/preparation.html.twig');
    }
}
