<?php
declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\AddItemToCart;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Value\CartItem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class AddItemToCartHandler
{
    public function __construct(
        private CartStorage $cartStorage,
        private GetProducts $getProducts,
    ) {
    }

    public function __invoke(AddItemToCart $message): void
    {
        $product =$this->getProducts->oneById($message->productId);

        $item = new CartItem(
            $message->productId,
            $message->quantity,
            slice: $product->forceSlicing ? true : $message->slice,
            pack: $product->forcePacking ? true : $message->pack,
        );

        $this->cartStorage->addItem($item);
    }
}
