<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetPlaces;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

final class OrderProgressPanelController extends AbstractController
{
    public function __construct(
        readonly private GetPlaces $getPlaces,
        readonly private RequestStack $requestStack,
    ) {
    }

    public function __invoke(): Response
    {
        return $this->render('_order_panel.html.twig', [
            'places' => $this->getPlaces->all(),
            'currentUri' => $this->requestStack->getMainRequest()?->getRequestUri(),
        ]);
    }
}
