<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Calendar;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\CalendarColdFormData;
use CeskaKruta\Web\FormType\Admin\CalendarColdFormType;
use CeskaKruta\Web\Message\Admin\Calendar\EditCalendarCold;
use CeskaKruta\Web\Query\Admin\GetAdminCalendarCold;
use CeskaKruta\Web\Query\Admin\GetProductTypes;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CalendarColdEditController extends AbstractController
{
    public function __construct(
        readonly private GetAdminCalendarCold $getAdminCalendarCold,
        readonly private GetProductTypes $getProductTypes,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/chlazene-kruty/{id}', name: 'admin_calendar_cold_edit', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $row = $this->getAdminCalendarCold->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_calendar_cold');
        }

        $formData = CalendarColdFormData::fromCalendarCold($row);
        $form = $this->createForm(CalendarColdFormType::class, $formData, [
            'product_types' => $this->getProductTypes->choices(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(EditCalendarCold::fromFormData($id, $admin->id, $formData));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_calendar_cold_edit', ['id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_calendar_cold') . '#item-' . $id);
        }

        return $this->render('admin/calendar/cold_form.html.twig', [
            'form' => $form,
            'row' => $row,
        ]);
    }
}
