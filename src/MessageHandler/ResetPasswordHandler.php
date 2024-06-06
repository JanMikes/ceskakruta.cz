<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\ResetPassword;
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
    ) {
    }

    public function __invoke(ResetPassword $message): void
    {
        // invalidate token

        $this->userService->changePassword($message->email, $message->newPlainTextPassword);

        // Manually log in the user
        $user = $this->userProvider->loadUserByIdentifier($message->email);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }
}
