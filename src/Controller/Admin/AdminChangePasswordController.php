<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin;

use CeskaKruta\Web\FormData\Admin\ChangePasswordFormData;
use CeskaKruta\Web\FormType\Admin\ChangePasswordFormType;
use CeskaKruta\Web\Message\Admin\ChangeAdminPassword;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class AdminChangePasswordController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
        readonly private UserProvider $userProvider,
        readonly private Security $security,
    ) {
    }

    #[Route(path: '/admin/zmena-hesla', name: 'admin_change_password')]
    public function __invoke(#[CurrentUser] User $loggedUser, Request $request): Response
    {
        $formData = new ChangePasswordFormData();
        $form = $this->createForm(ChangePasswordFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(
                new ChangeAdminPassword($loggedUser->id, $formData->newPassword),
            );

            // The session token holds the old password hash, without a fresh login the admin would be logged out
            $this->security->login($this->userProvider->loadUserByIdentifier($loggedUser->email), 'form_login', 'admin');

            $this->addFlash('success', 'Heslo bylo úspěšně změněno!');

            return $this->redirectToRoute('admin_change_password');
        }

        return $this->render('admin/change_password.html.twig', [
            'form' => $form,
        ]);
    }
}
