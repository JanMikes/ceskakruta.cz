<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetRecipes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RecipeDetailController extends AbstractController
{
    public function __construct(
        readonly private GetRecipes $getRecipes,
    ) {
    }

    #[Route(path: '/recept/{recipeId}', name: 'recipe_detail')]
    public function __invoke(int|string $recipeId): Response
    {
        try {
            $recipe = $this->getRecipes->oneById($recipeId);
        } catch (\Throwable) {
            throw $this->createNotFoundException();
        }

        return $this->render('recipe_detail.html.twig', [
            'recipe' => $recipe,
        ]);
    }
}
