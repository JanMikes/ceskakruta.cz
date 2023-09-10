<?php
declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\RemoveItemFromCart;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RemoveItemFromCartHandler
{
    public function __invoke(RemoveItemFromCart $message): void
    {

    }
}
