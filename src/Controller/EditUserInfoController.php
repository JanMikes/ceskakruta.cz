<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\UserInfoFormData;
use CeskaKruta\Web\FormType\UserInfoFormType;
use CeskaKruta\Web\Message\EditUserInfo;
use CeskaKruta\Web\Message\LoadBillingInfoFromAres;
use CeskaKruta\Web\Value\User;
use h4kuna\Ares\Exceptions\IdentificationNumberNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class EditUserInfoController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/uzivatel/upravit-muj-ucet', name: 'edit_user_info')]
    public function __invoke(#[CurrentUser] User $loggedUser, Request $request): Response
    {
        $formData = UserInfoFormData::fromUser($loggedUser);
        $form = $this->createForm(UserInfoFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('action', 'save');

            if ($action === 'load_ares') {
                try {
                    $this->bus->dispatch(
                        LoadBillingInfoFromAres::fromFormData($loggedUser->email, $formData),
                    );

                    $this->addFlash('success', 'Údaje načteny z ARES a uloženy');
                } catch (HandlerFailedException $handlerFailedException) {
                    $previousException = $handlerFailedException->getPrevious();

                    if ($previousException instanceof IdentificationNumberNotFoundException) {
                        $this->addFlash('warning', 'Zadané IČ nebylo v ARES nalezeno. Ostatní údaje uloženy');
                    } else {
                        $this->addFlash('warning', 'Nepovedlo se načíst data z ARES. Ostatní údaje uloženy');
                    }

                    return $this->redirectToRoute('edit_user_info');
                }
            } else {
                $this->bus->dispatch(
                    EditUserInfo::fromFormData($loggedUser->email, $formData),
                );

                $this->addFlash('success', 'Údaje upraveny');
            }

            return $this->redirectToRoute('user_my_account');
        }

        return $this->render('edit_user_info.html.twig', [
            'form' => $form,
        ]);
    }
}
