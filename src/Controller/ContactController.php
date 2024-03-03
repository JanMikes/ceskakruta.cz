<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route(path: '/kontakt', name: 'contact', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('contact.html.twig');
    }
}
