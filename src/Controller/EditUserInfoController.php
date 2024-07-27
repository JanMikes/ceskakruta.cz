<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\UserInfoFormData;
use CeskaKruta\Web\FormType\UserInfoFormType;
use CeskaKruta\Web\Message\EditUserInfo;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class EditUserInfoController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/uzivatel/upravit-muj-ucet', name: 'edit_user_info', methods: ['GET', 'POST'])]
    public function __invoke(#[CurrentUser] User $loggedUser, Request $request): Response
    {
        $formData = UserInfoFormData::fromUser($loggedUser);
        $form = $this->createForm(UserInfoFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->bus->dispatch(
                EditUserInfo::fromFormData($loggedUser->email, $formData),
            );

            $this->addFlash('success', 'Údaje upraveny');

            return $this->redirectToRoute('user_my_account');
        }

        return $this->render('edit_user_info.html.twig', [
            'form' => $form,
        ]);
    }
}
