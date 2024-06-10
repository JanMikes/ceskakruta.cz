<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Exceptions\InvalidResetPasswordToken;
use CeskaKruta\Web\Exceptions\UserNotRegistered;
use CeskaKruta\Web\FormData\ChangePasswordFormData;
use CeskaKruta\Web\FormData\RequestPasswordResetFormData;
use CeskaKruta\Web\FormType\ChangePasswordFormType;
use CeskaKruta\Web\FormType\RequestPasswordResetFormType;
use CeskaKruta\Web\Message\RequestPasswordReset;
use CeskaKruta\Web\Message\ResetPassword;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ResetPasswordController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {}

    #[Route(path: '/obnoveni-hesla/{token}', name: 'reset_password', methods: ['GET', 'POST'])]
    public function __invoke(#[CurrentUser] null|User $user, Request $request, string $token): Response
    {
        if ($user !== null) {
            return $this->redirectToRoute('user_my_account');
        }

        $formData = new ChangePasswordFormData();
        $form = $this->createForm(ChangePasswordFormType::class, $formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $this->bus->dispatch(
                    new ResetPassword($token, $formData->password),
                );

                // Redirect to some route, e.g., the homepage
                return $this->redirectToRoute('user_my_account');
            } catch (HandlerFailedException $failedException) {
                $realException = $failedException->getPrevious();

                if ($realException instanceof InvalidResetPasswordToken) {
                    $this->addFlash('danger', 'Neplatný odkaz pro obnovu hesla, zkuste to prosím znovu.');

                    return $this->redirectToRoute('forgotten_password');
                }

                throw $realException ?? $failedException;
            }
        }

        return $this->render('reset_password.html.twig', [
            'form' => $form,
        ]);
    }
}
