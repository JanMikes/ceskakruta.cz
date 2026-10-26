<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Recipe;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\RecipeFormData;
use CeskaKruta\Web\FormType\Admin\RecipeFormType;
use CeskaKruta\Web\Message\Admin\Recipe\CreateRecipe;
use CeskaKruta\Web\Query\Admin\GetAdminProducts;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class RecipeCreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        readonly private GetAdminProducts $getAdminProducts,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/produkty/{productId}/recepty/pridat', name: 'admin_recipe_create', requirements: ['productId' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $productId): Response
    {
        try {
            $product = $this->getAdminProducts->oneById($productId);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_products');
        }

        $formData = new RecipeFormData();
        $form = $this->createForm(RecipeFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(CreateRecipe::fromFormData($admin->id, $productId, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_recipes', ['productId' => $productId]) . '#item-' . $id);
        }

        return $this->render('admin/recipe/form.html.twig', [
            'form' => $form,
            'product' => $product,
            'recipe' => null,
        ]);
    }
}
