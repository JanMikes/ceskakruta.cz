<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TradeTermsController extends AbstractController
{
    #[Route(path: '/obchodni-podminky', name: 'trade_terms')]
    public function __invoke(): Response
    {
        return $this->render('trade_terms.html.twig');
    }
}
