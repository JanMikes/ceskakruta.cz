<?php
declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\AddItemToCart;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Value\CartItem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class AddItemToCartHandler
{
    public function __construct(
        private CartStorage $cartStorage,
    ) {
    }

    public function __invoke(AddItemToCart $message): void
    {
        $item = new CartItem($message->productId, $message->quantity);

        $this->cartStorage->addItem($item);
    }
}
