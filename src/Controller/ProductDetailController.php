<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\AddToCartFormData;
use CeskaKruta\Web\FormType\AddToCartFormType;
use CeskaKruta\Web\Message\AddItemToCart;
use CeskaKruta\Web\Repository\ProductRepository;
use CeskaKruta\Web\Repository\ProductVariantRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProductDetailController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductVariantRepository $productVariantRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route(path: '/product/{productId}', name: 'product_detail', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, string $productId): Response
    {
        $product = $this->productRepository->get(Uuid::fromString($productId));
        $variants = $this->productVariantRepository->findAll();

        $addToCartForm = $this->createForm(AddToCartFormType::class, options: [
            'variants' => $variants,
        ]);

        $addToCartForm->handleRequest($request);

        if ($addToCartForm->isSubmitted() && $addToCartForm->isValid()) {
            $addToCartFormData = $addToCartForm->getData();
            assert($addToCartFormData instanceof AddToCartFormData);

            $this->messageBus->dispatch(
                new AddItemToCart(
                    Uuid::fromString($addToCartFormData->variantId),
                )
            );

            return $this->redirectToRoute('product_detail', ['productId' => $productId]);
        }

        return $this->render('product_detail.html.twig', [
            'product' => $product,
            'variants' => $variants,
            'add_to_cart_form' => $addToCartForm,
        ]);
    }
}
