<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Calendar;

use CeskaKruta\Web\FormData\Admin\CalendarAliveFormData;
use CeskaKruta\Web\FormType\Admin\CalendarAliveFormType;
use CeskaKruta\Web\Message\Admin\Calendar\CreateCalendarAlive;
use CeskaKruta\Web\Query\Admin\GetProductTypes;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CalendarAliveCreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        readonly private GetProductTypes $getProductTypes,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/zive-kruty/pridat', name: 'admin_calendar_alive_create')]
    public function __invoke(#[CurrentUser] User $admin, Request $request): Response
    {
        $formData = new CalendarAliveFormData();
        $form = $this->createForm(CalendarAliveFormType::class, $formData, [
            'product_types' => $this->getProductTypes->choices(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(CreateCalendarAlive::fromFormData($admin->id, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_calendar_alive') . '#item-' . $id);
        }

        return $this->render('admin/calendar/alive_form.html.twig', [
            'form' => $form,
            'row' => null,
        ]);
    }
}
