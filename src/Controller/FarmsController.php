<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FarmsController extends AbstractController
{
    #[Route(path: '/farmy-ceske-kruty', name: 'farms')]
    public function __invoke(): Response
    {
        return $this->render('farms.html.twig');
    }
}
