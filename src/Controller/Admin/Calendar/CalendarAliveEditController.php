<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Calendar;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\FormData\Admin\CalendarAliveFormData;
use CeskaKruta\Web\FormType\Admin\CalendarAliveFormType;
use CeskaKruta\Web\Message\Admin\Calendar\EditCalendarAlive;
use CeskaKruta\Web\Query\Admin\GetAdminCalendarAlive;
use CeskaKruta\Web\Query\Admin\GetProductTypes;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CalendarAliveEditController extends AbstractController
{
    public function __construct(
        readonly private GetAdminCalendarAlive $getAdminCalendarAlive,
        readonly private GetProductTypes $getProductTypes,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/admin/zive-kruty/{id}', name: 'admin_calendar_alive_edit', requirements: ['id' => '\d+'])]
    public function __invoke(#[CurrentUser] User $admin, Request $request, int $id): Response
    {
        try {
            $row = $this->getAdminCalendarAlive->oneById($id);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_calendar_alive');
        }

        $formData = CalendarAliveFormData::fromCalendarAlive($row);
        $form = $this->createForm(CalendarAliveFormType::class, $formData, [
            'product_types' => $this->getProductTypes->choices(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(EditCalendarAlive::fromFormData($id, $admin->id, $formData));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            if ($request->request->has('_continue')) {
                return $this->redirectToRoute('admin_calendar_alive_edit', ['id' => $id]);
            }

            return $this->redirect($this->generateUrl('admin_calendar_alive') . '#item-' . $id);
        }

        return $this->render('admin/calendar/alive_form.html.twig', [
            'form' => $form,
            'row' => $row,
        ]);
    }
}
