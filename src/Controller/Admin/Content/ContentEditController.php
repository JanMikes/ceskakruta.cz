<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Content;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\ContentFormData;
use CeskaKruta\Web\FormType\Admin\ContentFormType;
use CeskaKruta\Web\Message\Admin\Content\EditContent;
use CeskaKruta\Web\Query\Admin\GetAdminContents;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ContentEditController extends AbstractController
{
    public function __construct(
        readonly private GetAdminContents $getAdminContents,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/texty/{id}', name: 'admin_content_edit', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $content = $this->getAdminContents->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_contents');
        }

        $formData = ContentFormData::fromContent($content);
        $form = $this->createForm(ContentFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(EditContent::fromFormData($id, $admin->id, $formData));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_content_edit', ['id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_contents') . '#item-' . $id);
        }

        return $this->render('admin/content/form.html.twig', [
            'form' => $form,
            'content' => $content,
        ]);
    }
}
