<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\ChangePasswordFormData;
use CeskaKruta\Web\FormData\UserInfoFormData;
use CeskaKruta\Web\FormType\ChangePasswordFormType;
use CeskaKruta\Web\FormType\UserInfoFormType;
use CeskaKruta\Web\Message\ChangePassword;
use CeskaKruta\Web\Message\EditUserInfo;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ChangeUserPasswordController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/uzivatel/zmena-hesla', name: 'change_user_password', methods: ['GET', 'POST'])]
    public function __invoke(#[CurrentUser] User $loggedUser, Request $request): Response
    {
        $formData = new ChangePasswordFormData();
        $form = $this->createForm(ChangePasswordFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(
                new ChangePassword($loggedUser->email, $formData->password),
            );

            $this->addFlash('success', 'Heslo změněno');

            return $this->redirectToRoute('user_my_account');
        }

        return $this->render('change_user_password.html.twig', [
            'form' => $form,
        ]);
    }
}
