<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Actuality;

use CeskaKruta\Web\FormData\Admin\ActualityFormData;
use CeskaKruta\Web\FormType\Admin\ActualityFormType;
use CeskaKruta\Web\Message\Admin\Actuality\CreateActuality;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ActualityCreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route(path: '/admin/aktuality/pridat', name: 'admin_actuality_create')]
    public function __invoke(#[CurrentUser] User $admin, Request $request): Response
    {
        $formData = new ActualityFormData();
        $form = $this->createForm(ActualityFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->handle(CreateActuality::fromFormData($admin->id, $formData));
            assert(is_int($id));

            $this->addFlash('success', 'Záznam byl úspěšně uložen!');

            return $this->redirect($this->generateUrl('admin_actualities') . '#item-' . $id);
        }

        return $this->render('admin/actuality/form.html.twig', [
            'form' => $form,
            'actuality' => null,
        ]);
    }
}
