<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Sortiment;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\SortimentFormData;
use CeskaKruta\Web\FormType\Admin\SortimentFormType;
use CeskaKruta\Web\Message\Admin\Sortiment\EditSortimentItem;
use CeskaKruta\Web\Query\Admin\GetAdminSortiment;
use CeskaKruta\Web\Query\Admin\GetSortimentCategories;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SortimentEditController extends AbstractController
{
    public function __construct(
        readonly private GetSortimentCategories $getSortimentCategories,
        readonly private GetAdminSortiment $getAdminSortiment,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/sortiment/{categoryId}/{id}', name: 'admin_sortiment_edit', requirements: ['categoryId' => '\d+', 'id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $categoryId, int $id): Response
    {
        try {
            $category = $this->getSortimentCategories->oneById($categoryId);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_sortiment');
        }

        try {
            $item = $this->getAdminSortiment->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_sortiment_category', ['categoryId' => $categoryId]);
        }

        if ($item->categoryId !== $categoryId) {
            return $this->redirectToRoute('admin_sortiment_category', ['categoryId' => $categoryId]);
        }

        $formData = SortimentFormData::fromItem($item);
        $form = $this->createForm(SortimentFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(EditSortimentItem::fromFormData($id, $admin->id, $formData));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_sortiment_edit', ['categoryId' => $categoryId, 'id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_sortiment_category', ['categoryId' => $categoryId]) . '#item-' . $id);
        }

        return $this->render('admin/sortiment/form.html.twig', [
            'form' => $form,
            'category' => $category,
            'item' => $item,
        ]);
    }
}
