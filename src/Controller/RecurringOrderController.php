<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\SaveRecurringOrder;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Repository\RecurringOrderRepository;
use CeskaKruta\Web\Value\Product;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class RecurringOrderController extends AbstractController
{
    public function __construct(
        readonly private GetProducts $getProducts,
        readonly private RecurringOrderRepository $recurringOrderRepository,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/uzivatel/pravidelne-objednavky', name: 'user_recurring_order')]
    public function __invoke(#[CurrentUser] User $loggedUser, Request $request): Response
    {
        $products = $this->getProducts->all();
        $products = array_filter($products, function(Product $product): bool {
            return $product->isTurkey === false;
        });

        $ordersByDay = $this->recurringOrderRepository->getForUserByDay($loggedUser->id);

        /** @var null|string $day */
        $day = $request->query->get('day');

        if ($day !== null && $request->isMethod('POST')) {
            /** @var array<int, array{amount: array<string, string>}> $items */
            $items = $request->request->all('item');

            $this->bus->dispatch(
                new SaveRecurringOrder(
                    $loggedUser->id,
                    (int) $day,
                    $items,
                ),
            );

            $this->addFlash('success', 'Uložili jsme vaši pravidelnou objednávku.');

            return $this->redirectToRoute('user_recurring_order', ['day' => $day]);
        }

        return $this->render('user_recurring_order.html.twig', [
            'products' => $products,
            'day' => $day,
            'orders_by_day' => $ordersByDay,
        ]);
    }
}
