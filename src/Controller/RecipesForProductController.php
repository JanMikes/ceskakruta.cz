<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetRecipes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RecipesForProductController extends AbstractController
{
    public function __construct(
        readonly private GetRecipes $getRecipes,
    ) {
    }

    #[Route(path: '/recepty/{productId}', name: 'recipes_for_product', methods: ['GET'])]
    public function __invoke(int|string $productId): Response
    {
        try {
            $recipes = $this->getRecipes->getForProduct((int) $productId);
        } catch (\Throwable) {
            throw $this->createNotFoundException();
        }

        if (count($recipes) === 0) {
            throw $this->createNotFoundException();
        }

        return $this->render('recipes.html.twig', [
            'recipes' => $recipes,
        ]);
    }
}
