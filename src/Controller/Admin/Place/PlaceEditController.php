<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Place;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\PlaceFormData;
use CeskaKruta\Web\FormType\Admin\PlaceFormType;
use CeskaKruta\Web\Message\Admin\Place\EditPlace;
use CeskaKruta\Web\Query\Admin\GetAdminPlaces;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class PlaceEditController extends AbstractController
{
    public function __construct(
        readonly private GetAdminPlaces $getAdminPlaces,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/prodejny/{id}', name: 'admin_place_edit', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $place = $this->getAdminPlaces->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_places');
        }

        $formData = PlaceFormData::fromPlace($place);
        $form = $this->createForm(PlaceFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(EditPlace::fromFormData($id, $admin->id, $formData));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_place_edit', ['id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_places') . '#item-' . $id);
        }

        return $this->render('admin/place/form.html.twig', [
            'form' => $form,
            'place' => $place,
        ]);
    }
}
