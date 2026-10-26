<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Place;

use CeskaKruta\Web\FormData\Admin\PlaceClosedDayFormData;
use CeskaKruta\Web\FormType\Admin\PlaceClosedDayFormType;
use CeskaKruta\Web\Message\Admin\Place\AddPlaceClosedDay;
use CeskaKruta\Web\Query\Admin\GetAdminPlaces;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class PlaceClosedDayAddController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        readonly private GetAdminPlaces $getAdminPlaces,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/prodejny/zavreno/pridat', name: 'admin_place_closed_day_add')]
    public function __invoke(#[CurrentUser] User $admin, Request $request): Response
    {
        $formData = new PlaceClosedDayFormData();
        $form = $this->createForm(PlaceClosedDayFormType::class, $formData, [
            'places' => $this->getAdminPlaces->namesForChoice(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(AddPlaceClosedDay::fromFormData($admin->id, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_places') . '#closed-day-' . $id);
        }

        return $this->render('admin/place/closed_day_form.html.twig', [
            'form' => $form,
        ]);
    }
}
