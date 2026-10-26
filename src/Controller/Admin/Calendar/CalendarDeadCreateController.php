<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Calendar;

use CeskaKruta\Web\FormData\Admin\CalendarDeadFormData;
use CeskaKruta\Web\FormType\Admin\CalendarDeadFormType;
use CeskaKruta\Web\Message\Admin\Calendar\CreateCalendarDead;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CalendarDeadCreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/rozkrmena-krutata/pridat', name: 'admin_calendar_dead_create')]
    public function __invoke(#[CurrentUser] User $admin, Request $request): Response
    {
        $formData = new CalendarDeadFormData();
        $form = $this->createForm(CalendarDeadFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(CreateCalendarDead::fromFormData($admin->id, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_calendar_dead') . '#item-' . $id);
        }

        return $this->render('admin/calendar/dead_form.html.twig', [
            'form' => $form,
            'row' => null,
        ]);
    }
}
