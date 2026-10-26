<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\User;

use CeskaKruta\Web\Query\Admin\GetAdminUsers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserListController extends AbstractController
{
    public function __construct(
        readonly private GetAdminUsers $getAdminUsers,
    ) {
    }

    #[Route(path: '/admin/uzivatele', name: 'admin_users')]
    public function __invoke(): Response
    {
        return $this->render('admin/user/list.html.twig', [
            'users' => $this->getAdminUsers->all(),
        ]);
    }
}
