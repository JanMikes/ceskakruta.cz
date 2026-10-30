<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Sortiment;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\SortimentCategoryFormData;
use CeskaKruta\Web\FormType\Admin\SortimentCategoryFormType;
use CeskaKruta\Web\Message\Admin\Sortiment\EditSortimentCategory;
use CeskaKruta\Web\Query\Admin\GetSortimentCategories;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SortimentCategoryEditController extends AbstractController
{
    public function __construct(
        readonly private GetSortimentCategories $getSortimentCategories,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/sortiment/kategorie/{id}', name: 'admin_sortiment_category_edit', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $category = $this->getSortimentCategories->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_sortiment');
        }

        $formData = SortimentCategoryFormData::fromCategory($category);
        $form = $this->createForm(SortimentCategoryFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(EditSortimentCategory::fromFormData($id, $admin->id, $formData));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_sortiment_category_edit', ['id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_sortiment') . '#item-' . $id);
        }

        return $this->render('admin/sortiment/category_form.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }
}
