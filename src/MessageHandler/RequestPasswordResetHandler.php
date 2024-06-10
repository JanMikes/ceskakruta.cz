<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\UserNotRegistered;
use CeskaKruta\Web\Message\RequestPasswordReset;
use CeskaKruta\Web\Services\PasswordResetTokenService;
use CeskaKruta\Web\Services\Security\UserProvider;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
readonly final class RequestPasswordResetHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private PasswordResetTokenService $passwordResetTokenService,
        private UserProvider $userProvider,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @throws UserNotRegistered
     */
    public function __invoke(RequestPasswordReset $message): void
    {
        $user = $this->userProvider->loadUserByIdentifier($message->email);

        $token = $this->passwordResetTokenService->create($user->id);

        $email = (new TemplatedEmail())
            ->to($message->email)
            ->subject('Obnovení hesla na ČeskáKrůta.cz')
            ->htmlTemplate('emails/forgotten_password.html.twig')
            ->context([
                'token_link' => $this->urlGenerator->generate(
                    'reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]);

        $this->mailer->send($email);
    }
}
