<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cart;

use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Value\CartItem;
use CeskaKruta\Web\Value\Currency;
use CeskaKruta\Web\Value\ProductVariantInCart;
use CeskaKruta\Web\Value\TotalPriceWithVat;
use Ramsey\Uuid\UuidInterface;

readonly final class Cart
{
    public function __construct(
        private GetProducts $getProducts,
        private CartStorage $cartStorage,
    ) {
    }

    public function itemsCount(): int
    {
        return count($this->cartStorage->getItems());
    }

    public function totalPrice(): TotalPriceWithVat
    {
        return new TotalPriceWithVat(0, Currency::CZK);

        /*
        $variantIds = array_map(
            static fn (CartItem $cartItem): UuidInterface => $cartItem->productVariantId,
            $this->cartStorage->getItems(),
        );

        $variantsInCart = $this->getVariantsInCart->byIds($variantIds);
        $totalWithVat = new TotalPriceWithVat(0, Currency::CZK);

        foreach ($variantsInCart as $variantInCart) {
            foreach ($this->cartStorage->getItems() as $item) {
                if ($item->productVariantId->equals($variantInCart->id)) {
                    $totalWithVat = $totalWithVat->add($variantInCart->price->valueWithoutVat);
                }
            }
        }

        return $totalWithVat;
        */
    }

    /**
     * @return list<ProductVariantInCart>
     */
    public function items(): array
    {
        $products = $this->getProducts->all();

        // $variantsInCart = $this->getVariantsInCart->byIds($variantIds);
        $productsInCart = [];
        $productItemsInCart = [];

        return $productItemsInCart;

        /*
        foreach ($productsInCart as $productInCart) {
            foreach ($this->cartStorage->getItems() as $item) {
                if ($item->productId === $productInCart->id) {
                    $productItemsInCart[] = new $productInCart;
                }
            }
        }

        return $productItemsInCart;
        */
    }
}
