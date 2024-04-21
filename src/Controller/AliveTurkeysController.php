<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AliveTurkeysController extends AbstractController
{
    #[Route(path: '/krutata-k-chovu', name: 'alive_turkeys', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('alive_turkeys.html.twig');
    }
}
