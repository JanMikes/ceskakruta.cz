<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Exceptions\UserNotRegistered;
use CeskaKruta\Web\FormData\RequestPasswordResetFormData;
use CeskaKruta\Web\FormType\RequestPasswordResetFormType;
use CeskaKruta\Web\Message\RequestPasswordReset;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ForgottenPasswordController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {}

    #[Route(path: '/zapomenute-heslo', name: 'forgotten_password')]
    public function __invoke(#[CurrentUser] null|User $user, Request $request): Response
    {
        if ($user !== null) {
            return $this->redirectToRoute('user_my_account');
        }

        $formData = new RequestPasswordResetFormData();
        $form = $this->createForm(RequestPasswordResetFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $this->bus->dispatch(
                    new RequestPasswordReset($formData->email),
                );

                $this->addFlash('success', 'Poslali jsme vám e-mail s instrukcemi pro obnovu vašeho zapomenutého hesla. Pokud Vám zpráva nedorazí, zkontrolujte pro jistotu složku SPAM.');

                return $this->redirectToRoute('homepage');
            } catch (HandlerFailedException $failedException) {
                $realException = $failedException->getPrevious();

                if ($realException instanceof UserNotRegistered) {
                    $this->addFlash('danger', 'Tento e-mail u nás není zaregistrován.');

                    return $this->redirectToRoute('forgotten_password');
                }

                throw $realException ?? $failedException;
            }
        }

        return $this->render('forgotten_password.html.twig', [
            'form' => $form,
        ]);
    }
}
