<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetActualities;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActualityController extends AbstractController
{
    public function __construct(
        readonly private GetActualities $getActualities,
    ) {
    }

    #[Route(path: '/aktuality', name: 'actuality')]
    public function __invoke(): Response
    {
        return $this->render('actuality.html.twig', [
            'actualities' => $this->getActualities->all(),
        ]);
    }
}
