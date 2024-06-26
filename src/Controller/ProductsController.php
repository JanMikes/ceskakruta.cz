<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\AddToCartFormData;
use CeskaKruta\Web\FormType\AddToCartFormType;
use CeskaKruta\Web\Message\AddItemToCart;
use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Query\GetProducts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProductsController extends AbstractController
{
    public function __construct(
        readonly private GetProducts $getProducts,
        readonly private MessageBusInterface $bus,
        readonly private GetColdProductsCalendar $getColdProductsCalendar,
    ) {}

    #[Route(path: '/nase-nabidka/kruty-a-krocani', name: 'products_cold', methods: ['GET', 'POST'])]
    #[Route(path: '/nase-nabidka', name: 'products', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        /** @var string $routeName */
        $routeName = $request->attributes->get('_route');

        $products = $this->getProducts->all();

        /** @var array<Form> $forms */
        $forms = [];
        /** @var array<FormView> $formViews */
        $formViews = [];

        foreach ($products as $product) {
            $data = new AddToCartFormData();
            $data->productId = (string) $product->id;

            $form = $this->createForm(AddToCartFormType::class, $data, [
                'action' => $this->generateUrl($routeName, ['productId' => $product->id]),
            ]);

            $forms[$product->id] = $form;
            $formViews[$product->id] = $form->createView();
        }

        /** @var null|string $submittedProductId */
        $submittedProductId = $request->query->get('productId');

        if ($submittedProductId !== null) {
            $requestForm = $forms[(int) $submittedProductId];
            $requestForm->handleRequest($request);

            if ($requestForm->isSubmitted() && $requestForm->isValid()) {
                $data = $requestForm->getData();
                assert($data instanceof AddToCartFormData);

                $this->bus->dispatch(
                    new AddItemToCart(
                        productId: (int) $data->productId,
                        quantity: $data->quantity,
                        slice: $data->slice,
                        pack: $data->pack,
                    ),
                );

                $this->addFlash('success', 'Přidáno do košíku');

                return $this->redirectToRoute($routeName);
            }
        }

        $now = new \DateTimeImmutable();

        return $this->render($routeName === 'products' ? 'products.html.twig' : 'products_cold.html.twig', [
            'products' => $products,
            'products_halves' => $this->getProducts->getHalves(),
            'add_to_cart_forms' =>  $formViews,
            'current_year' => $now->format('Y'),
            'current_week' => $now->format('W'),
            'calendar' => $this->getColdProductsCalendar->all(),
        ]);
    }
}
