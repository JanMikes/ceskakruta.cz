<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Calendar;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\CalendarDeadFormData;
use CeskaKruta\Web\FormType\Admin\CalendarDeadFormType;
use CeskaKruta\Web\Message\Admin\Calendar\EditCalendarDead;
use CeskaKruta\Web\Query\Admin\GetAdminCalendarDead;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CalendarDeadEditController extends AbstractController
{
    public function __construct(
        readonly private GetAdminCalendarDead $getAdminCalendarDead,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/rozkrmena-krutata/{id}', name: 'admin_calendar_dead_edit', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $row = $this->getAdminCalendarDead->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_calendar_dead');
        }

        $formData = CalendarDeadFormData::fromCalendarDead($row);
        $form = $this->createForm(CalendarDeadFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(EditCalendarDead::fromFormData($id, $admin->id, $formData));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_calendar_dead_edit', ['id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_calendar_dead') . '#item-' . $id);
        }

        return $this->render('admin/calendar/dead_form.html.twig', [
            'form' => $form,
            'row' => $row,
        ]);
    }
}
