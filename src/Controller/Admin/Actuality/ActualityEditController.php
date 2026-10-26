<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Actuality;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\ActualityFormData;
use CeskaKruta\Web\FormType\Admin\ActualityFormType;
use CeskaKruta\Web\Message\Admin\Actuality\EditActuality;
use CeskaKruta\Web\Query\Admin\GetAdminActualities;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ActualityEditController extends AbstractController
{
    public function __construct(
        readonly private GetAdminActualities $getAdminActualities,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/aktuality/{id}', name: 'admin_actuality_edit', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $actuality = $this->getAdminActualities->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_actualities');
        }

        $formData = ActualityFormData::fromActuality($actuality);
        $form = $this->createForm(ActualityFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(EditActuality::fromFormData($id, $admin->id, $formData));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_actuality_edit', ['id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_actualities') . '#item-' . $id);
        }

        return $this->render('admin/actuality/form.html.twig', [
            'form' => $form,
            'actuality' => $actuality,
        ]);
    }
}
