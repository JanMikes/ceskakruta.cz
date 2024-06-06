<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\RequestPasswordReset;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class RequestPasswordResetHandler
{
    public function __invoke(RequestPasswordReset $message): void
    {
        // generate token + save to database
        // send email with link
    }
}
