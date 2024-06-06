<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\InvalidResetPasswordToken;
use CeskaKruta\Web\Exceptions\UserNotRegistered;
use CeskaKruta\Web\Message\ResetPassword;
use CeskaKruta\Web\Services\PasswordResetTokenService;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Services\UserService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[AsMessageHandler]
readonly final class ResetPasswordHandler
{
    public function __construct(
        private UserProvider $userProvider,
        private TokenStorageInterface $tokenStorage,
        private UserService $userService,
        private PasswordResetTokenService $passwordResetTokenService,
    ) {
    }

    /**
     * @throws InvalidResetPasswordToken
     * @throws UserNotRegistered
     */
    public function __invoke(ResetPassword $message): void
    {
        $userId = $this->passwordResetTokenService->getTokenUserId($message->token);
        $email = $this->userService->getEmailById($userId);

        $this->userService->changePassword($email, $message->newPlainTextPassword);

        $this->passwordResetTokenService->useToken($message->token);

        // Manually log in the user
        $user = $this->userProvider->loadUserByIdentifier($email);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }
}
