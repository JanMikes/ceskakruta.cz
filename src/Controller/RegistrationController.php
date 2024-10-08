<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Exceptions\UserAlreadyRegistered;
use CeskaKruta\Web\FormData\RegistrationFormData;
use CeskaKruta\Web\FormType\RegistrationFormType;
use CeskaKruta\Web\Message\RegisterUser;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class RegistrationController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {}

    #[Route(path: '/registrace', name: 'registration')]
    public function __invoke(#[CurrentUser] null|User $user, Request $request): Response
    {
        if ($user !== null) {
            return $this->redirectToRoute('user_my_account');
        }

        $registrationData = new RegistrationFormData();
        $form = $this->createForm(RegistrationFormType::class, $registrationData);
        $error = null;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $this->bus->dispatch(
                    new RegisterUser(
                        $registrationData->email,
                        $registrationData->password,
                        $registrationData->name,
                        $registrationData->phone,
                    ),
                );

                $this->addFlash('success', 'Úspěšně jste se zaregistrovali a rovnou jsme vás přihlásili. Přejeme příjemné nakupování! <a href="' . $this->generateUrl("products") . '">Pokračovat k naší nabídce</a>.');

                return $this->redirectToRoute('user_my_account');
            } catch (HandlerFailedException $failedException) {
                $realException = $failedException->getPrevious();

                if ($realException instanceof UserAlreadyRegistered) {
                    $this->addFlash('danger', 'Tento e-mail je u nás již zaregistrován, zkuste se prosím přihlásit.');

                    return $this->redirectToRoute('login');
                }

                throw $realException ?? $failedException;
            }
        }

        return $this->render('registration.html.twig', [
            'registration_form' => $form,
            'error' => $error,
        ]);
    }
}
