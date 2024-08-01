<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutUsController extends AbstractController
{
    #[Route(path: '/o-nas', name: 'about_us')]
    public function __invoke(): Response
    {
        return $this->render('about_us.html.twig');
    }
}
