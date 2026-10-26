<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Place;

use CeskaKruta\Web\FormData\Admin\PlaceFormData;
use CeskaKruta\Web\FormType\Admin\PlaceFormType;
use CeskaKruta\Web\Message\Admin\Place\CreatePlace;
use CeskaKruta\Web\Query\Admin\GetAdminPlaces;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class PlaceCreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        readonly private GetAdminPlaces $getAdminPlaces,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/prodejny/pridat', name: 'admin_place_create')]
    public function __invoke(#[CurrentUser] User $admin, Request $request): Response
    {
        $formData = new PlaceFormData();
        $form = $this->createForm(PlaceFormType::class, $formData, [
            'price_places' => $this->getAdminPlaces->namesForChoice(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(CreatePlace::fromFormData($admin->id, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_places') . '#item-' . $id);
        }

        return $this->render('admin/place/form.html.twig', [
            'form' => $form,
            'place' => null,
        ]);
    }
}
