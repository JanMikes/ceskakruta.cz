<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActualityController extends AbstractController
{
    #[Route(path: '/aktuality', name: 'actuality', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('actuality.html.twig');
    }
}
