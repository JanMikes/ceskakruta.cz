<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\AddToCartFormData;
use CeskaKruta\Web\FormType\AddToCartFormType;
use CeskaKruta\Web\Message\AddItemToCart;
use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Query\GetRecipes;
use CeskaKruta\Web\Services\Calendar;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

final class ProductsController extends AbstractController
{
    public function __construct(
        readonly private GetProducts $getProducts,
        readonly private MessageBusInterface $bus,
        readonly private GetColdProductsCalendar $getColdProductsCalendar,
        readonly private GetRecipes $getRecipes,
        readonly private CartStorage $cartStorage,
    ) {}

    #[Route(path: '/nase-nabidka/kruty-a-krocani', name: 'products_cold')]
    #[Route(path: '/nase-nabidka', name: 'products')]
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
                'csrf_protection' => false,
            ]);

            $forms[$product->id] = $form;
            $formViews[$product->id] = $form->createView();
        }

        /** @var null|string $submittedProductId */
        $submittedProductId = $request->query->get('productId');

        if ($submittedProductId !== null) {
            $requestForm = $forms[(int) $submittedProductId] ?? null;

            if ($requestForm !== null) {
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

                    if ($data->year !== null && $data->week !== null) {
                        $this->cartStorage->storeLockedWeek($data->year, $data->week);
                    }

                    if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                        return $this->render('cart_item_added.stream.html.twig', [
                           'product' => $products[(int) $data->productId],
                        ]);
                    }

                    $this->addFlash('success', 'Přidáno do košíku');

                    return $this->redirectToRoute($routeName);
                }
            }
        }

        $recipesCount = [];
        foreach ($products as $product) {
            $recipesCount[$product->id] = $this->getRecipes->getCountForProduct($product->id);
        }

        return $this->render($routeName === 'products' ? 'products.html.twig' : 'products_cold.html.twig', [
            'products' => $products,
            'recipes_count' => $recipesCount,
            'products_halves' => $this->getProducts->getHalves(),
            'add_to_cart_forms' =>  $formViews,
            'calendar' => $this->getColdProductsCalendar->all(),
        ]);
    }
}
