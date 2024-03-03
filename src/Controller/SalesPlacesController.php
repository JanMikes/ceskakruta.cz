<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SalesPlacesController extends AbstractController
{
    #[Route(path: '/nase-prodejny', name: 'sales_places', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('sales_places.html.twig');
    }
}
