<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Recipe;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Query\Admin\CountInactiveRecords;
use CeskaKruta\Web\Query\Admin\GetAdminProducts;
use CeskaKruta\Web\Query\Admin\GetAdminRecipes;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RecipeListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminProducts $getAdminProducts,
        readonly private GetAdminRecipes $getAdminRecipes,
        readonly private CountInactiveRecords $countInactiveRecords,
    ) {
    }

    #[Route(path: '/admin/produkty/{productId}/recepty', name: 'admin_recipes', requirements: ['productId' => '\d+'])]
    public function __invoke(int $productId): Response
    {
        try {
            $product = $this->getAdminProducts->oneById($productId);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/recipe/list.html.twig', [
            'product' => $product,
            'recipes' => $this->getAdminRecipes->allOfProduct($productId),
            'inactive_count' => $this->countInactiveRecords->count(AdminTable::Recipe, $productId),
        ]);
    }
}
