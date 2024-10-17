<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderContactInfoController extends AbstractController
{
    #[Route(path: '/objednavka/kontaktni-udaje', name: 'order_contact_info')]
    public function __invoke(): Response
    {
        return $this->render('order_contact_info.html.twig');
    }
}
