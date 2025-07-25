<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\AddToCartFormData;
use CeskaKruta\Web\FormType\AddToCartFormType;
use CeskaKruta\Web\Message\AddItemToCart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProductDetailController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route(path: '/product/{productId}', name: 'product_detail')]
    public function __invoke(Request $request, string $productId): Response
    {
        $addToCartForm = $this->createForm(AddToCartFormType::class, options: [
            'csrf_protection' => false,
        ]);

        $addToCartForm->handleRequest($request);

        if ($addToCartForm->isSubmitted() && $addToCartForm->isValid()) {
            $addToCartFormData = $addToCartForm->getData();
            assert($addToCartFormData instanceof AddToCartFormData);

            $this->messageBus->dispatch(
                new AddItemToCart(1, 1, null, null)
            );

            return $this->redirectToRoute('product_detail', ['productId' => $productId]);
        }

        return $this->render('product_detail.html.twig', [
            'add_to_cart_form' => $addToCartForm,
        ]);
    }
}
