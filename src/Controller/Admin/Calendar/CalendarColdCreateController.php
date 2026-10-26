<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Calendar;

use CeskaKruta\Web\FormData\Admin\CalendarColdFormData;
use CeskaKruta\Web\FormType\Admin\CalendarColdFormType;
use CeskaKruta\Web\Message\Admin\Calendar\CreateCalendarCold;
use CeskaKruta\Web\Query\Admin\GetProductTypes;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CalendarColdCreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        readonly private GetProductTypes $getProductTypes,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/chlazene-kruty/pridat', name: 'admin_calendar_cold_create')]
    public function __invoke(#[CurrentUser] User $admin, Request $request): Response
    {
        $formData = new CalendarColdFormData();
        $form = $this->createForm(CalendarColdFormType::class, $formData, [
            'product_types' => $this->getProductTypes->choices(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(CreateCalendarCold::fromFormData($admin->id, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_calendar_cold') . '#item-' . $id);
        }

        return $this->render('admin/calendar/cold_form.html.twig', [
            'form' => $form,
            'row' => null,
        ]);
    }
}
