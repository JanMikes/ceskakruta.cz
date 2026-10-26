<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Sortiment;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\SortimentFormData;
use CeskaKruta\Web\FormType\Admin\SortimentFormType;
use CeskaKruta\Web\Message\Admin\Sortiment\CreateSortimentItem;
use CeskaKruta\Web\Query\Admin\GetSortimentCategories;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SortimentCreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        readonly private GetSortimentCategories $getSortimentCategories,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/sortiment/{categoryId}/pridat', name: 'admin_sortiment_create', requirements: ['categoryId' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $categoryId): Response
    {
        try {
            $category = $this->getSortimentCategories->oneById($categoryId);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_sortiment');
        }

        $formData = new SortimentFormData();
        $form = $this->createForm(SortimentFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(CreateSortimentItem::fromFormData($admin->id, $categoryId, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_sortiment_category', ['categoryId' => $categoryId]) . '#item-' . $id);
        }

        return $this->render('admin/sortiment/form.html.twig', [
            'form' => $form,
            'category' => $category,
            'item' => null,
        ]);
    }
}
