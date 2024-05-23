<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Query\GetProducts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductsColdCalendarController extends AbstractController
{
    public function __construct(
        readonly private GetColdProductsCalendar $getColdProductsCalendar,
        readonly private GetProducts $getProducts,
    ) {}

    #[Route(path: '/nase-nabidka/kruty-a-krocani/vahovy-kalendar', name: 'products_cold_calendar', methods: ['GET'])]
    public function __invoke(): Response
    {
        $now = new \DateTimeImmutable();

        return $this->render('products_cold_calendar.html.twig', [
            'products' => $this->getProducts->all(),
            'current_year' => $now->format('Y'),
            'current_week' => $now->format('W'),
            'calendar' => $this->getColdProductsCalendar->all(),
        ]);
    }
}
