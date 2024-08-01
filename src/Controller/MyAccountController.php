<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MyAccountController extends AbstractController
{
    public function __construct(

    ) {
    }

    #[Route(path: '/uzivatel/muj-ucet', name: 'user_my_account')]
    public function __invoke(#[CurrentUser] User $loggedUser): Response
    {
        return $this->render('user_my_account.html.twig');
    }
}
