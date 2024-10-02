<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GdprController extends AbstractController
{
    #[Route(path: '/ochrana-osobnich-udaju', name: 'gdpr')]
    public function __invoke(): Response
    {
        return $this->render('gdpr.html.twig');
    }
}
