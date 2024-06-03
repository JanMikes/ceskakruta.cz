<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\EditUserInfo;
use CeskaKruta\Web\Services\UserService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditUserInfoHandler
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    public function __invoke(EditUserInfo $message): void
    {
        $this->userService->update($message);
    }
}
