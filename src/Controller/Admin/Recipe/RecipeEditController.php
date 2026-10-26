<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Recipe;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\RecipeFormData;
use CeskaKruta\Web\FormType\Admin\RecipeFormType;
use CeskaKruta\Web\Message\Admin\Recipe\EditRecipe;
use CeskaKruta\Web\Query\Admin\GetAdminProducts;
use CeskaKruta\Web\Query\Admin\GetAdminRecipes;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class RecipeEditController extends AbstractController
{
    public function __construct(
        readonly private GetAdminProducts $getAdminProducts,
        readonly private GetAdminRecipes $getAdminRecipes,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/produkty/{productId}/recepty/{id}', name: 'admin_recipe_edit', requirements: ['productId' => '\d+', 'id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $productId, int $id): Response
    {
        try {
            $product = $this->getAdminProducts->oneById($productId);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_products');
        }

        try {
            $recipe = $this->getAdminRecipes->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_recipes', ['productId' => $productId]);
        }

        if ($recipe->productId !== $productId) {
            return $this->redirectToRoute('admin_recipes', ['productId' => $productId]);
        }

        $formData = RecipeFormData::fromRecipe($recipe);
        $form = $this->createForm(RecipeFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(EditRecipe::fromFormData($id, $admin->id, $formData));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_recipe_edit', ['productId' => $productId, 'id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_recipes', ['productId' => $productId]) . '#item-' . $id);
        }

        return $this->render('admin/recipe/form.html.twig', [
            'form' => $form,
            'product' => $product,
            'recipe' => $recipe,
        ]);
    }
}
