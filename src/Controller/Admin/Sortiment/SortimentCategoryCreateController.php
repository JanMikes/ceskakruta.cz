<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Sortiment;

use CeskaKruta\Web\FormData\Admin\SortimentCategoryFormData;
use CeskaKruta\Web\FormType\Admin\SortimentCategoryFormType;
use CeskaKruta\Web\Message\Admin\Sortiment\CreateSortimentCategory;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SortimentCategoryCreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/sortiment/kategorie/pridat', name: 'admin_sortiment_category_create')]
    public function __invoke(#[CurrentUser] User $admin, Request $request): Response
    {
        $formData = new SortimentCategoryFormData();
        $form = $this->createForm(SortimentCategoryFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(CreateSortimentCategory::fromFormData($admin->id, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_sortiment') . '#item-' . $id);
        }

        return $this->render('admin/sortiment/category_form.html.twig', [
            'form' => $form,
            'category' => null,
        ]);
    }
}
