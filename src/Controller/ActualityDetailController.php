<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetActualities;
use CeskaKruta\Web\Query\GetRecipes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActualityDetailController extends AbstractController
{
    public function __construct(
        readonly private GetActualities $getActualities,
    ) {
    }

    #[Route(path: '/aktualita/{actualityId}', name: 'actuality_detail', methods: ['GET'])]
    public function __invoke(int|string $actualityId): Response
    {
        try {
            $actuality = $this->getActualities->oneById($actualityId);
        } catch (\Throwable) {
            throw $this->createNotFoundException();
        }

        return $this->render('actuality_detail.html.twig', [
            'actuality' => $actuality,
        ]);
    }
}
