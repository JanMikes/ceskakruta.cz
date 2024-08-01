<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutMeatController extends AbstractController
{
    #[Route(path: '/o-krutim-mase', name: 'about_meat')]
    public function __invoke(): Response
    {
        return $this->render('about_meat.html.twig');
    }
}
