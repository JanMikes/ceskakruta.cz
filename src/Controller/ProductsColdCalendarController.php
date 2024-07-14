<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\AddToCartFormData;
use CeskaKruta\Web\FormType\AddToCartFormType;
use CeskaKruta\Web\Message\AddItemToCart;
use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Query\GetProducts;
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

final class ProductsColdCalendarController extends AbstractController
{
    public function __construct(
        readonly private GetColdProductsCalendar $getColdProductsCalendar,
        readonly private GetProducts $getProducts,
        readonly private MessageBusInterface $bus,
        readonly private CartStorage $cartStorage,
        readonly private CartService $cartService,
        readonly private Calendar $calendar,
    ) {}

    #[Route(path: '/nase-nabidka/kruty-a-krocani/vahovy-kalendar', name: 'products_cold_calendar', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $products = $this->getProducts->all();

        /** @var array<Form> $forms */
        $forms = [];
        /** @var array<FormView> $formViews */
        $formViews = [];

        foreach ($products as $product) {
            $data = new AddToCartFormData();
            $data->productId = (string) $product->id;

            $form = $this->createForm(AddToCartFormType::class, $data, [
                'action' => $this->generateUrl('products_cold_calendar', ['productId' => $product->id]),
                'csrf_protection' => false,
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

                if ($data->week !== null && $data->year !== null) {
                    if ($this->cartService->containsTurkey()) {
                        $this->addFlash('warning', 'Změnili jste datum objednávky - z košíku jsme odstranili zboží, které v tomto termínu není dostupné');
                    }

                    $this->cartService->removeAllTurkeys();
                }

                $this->bus->dispatch(
                    new AddItemToCart(
                        productId: (int) $data->productId,
                        quantity: $data->quantity,
                        slice: null,
                        pack: null,
                    ),
                );

                $this->addFlash('success', 'Přidáno do košíku');

                $this->cartStorage->storeLockedWeek($data->year, $data->week);

                return $this->redirectToRoute('products_cold_calendar');
            }
        }

        $currentWeek = $this->calendar->getCurrentWeek();

        return $this->render('products_cold_calendar.html.twig', [
            'products' => $products,
            'current_year' => $currentWeek->year,
            'current_week' => $currentWeek->number,
            'calendar' => $this->getColdProductsCalendar->all(),
            'products_halves' => $this->getProducts->getHalves(),
            'add_to_cart_forms' =>  $formViews,
        ]);
    }
}
