<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\UserAlreadyRegistered;
use CeskaKruta\Web\Exceptions\UserNotRegistered;
use CeskaKruta\Web\Message\RegisterUser;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Services\UserService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[AsMessageHandler]
readonly final class RegisterUserHandler
{
    public function __construct(
        private UserProvider $userProvider,
        private UserService $userService,
        private TokenStorageInterface $tokenStorage,
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @throws UserAlreadyRegistered
     */
    public function __invoke(RegisterUser $message): void
    {
        try {
            $this->userProvider->loadUserByIdentifier($message->email);

            throw new UserAlreadyRegistered();
        } catch (UserNotRegistered) {
            $this->userService->register($message);

            // Manually log in the user
            $user = $this->userProvider->loadUserByIdentifier($message->email);
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->tokenStorage->setToken($token);

            $email = (new TemplatedEmail())
                ->to('j.mikes@me.com')
                ->subject('Potvrzení registrace účtu na ceskakruta.cz')
                ->htmlTemplate('emails/registration.html.twig')
                ->context([
                    'user' => $user,
                ]);

            $this->mailer->send($email);
        }
    }
}
