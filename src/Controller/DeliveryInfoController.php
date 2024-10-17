<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeliveryInfoController extends AbstractController
{
    #[Route(path: '/jak-funguje-rozvoz', name: 'delivery_info')]
    public function __invoke(): Response
    {
        return $this->render('delivery_info.html.twig');
    }
}
