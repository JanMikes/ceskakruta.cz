<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChooseDateController extends AbstractController
{
    #[Route(path: '/vybrat-datum-objednavky', name: 'choose_date', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('choose_date.html.twig');
    }
}
