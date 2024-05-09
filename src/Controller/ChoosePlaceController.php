<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChoosePlaceController extends AbstractController
{
    #[Route(path: '/vybrat-misto-odberu', name: 'choose_place', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('choose_place.html.twig');
    }
}
