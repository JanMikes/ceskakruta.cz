<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\User;

use CeskaKruta\Web\Query\Admin\GetAdminUsers;
use CeskaKruta\Web\Value\Admin\AdminUserFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserListController extends AbstractController
{
    private const PER_PAGE = 50;

    public function __construct(
        readonly private GetAdminUsers $getAdminUsers,
    ) {
    }

    #[Route(path: '/admin/uzivatele', name: 'admin_users')]
    public function __invoke(Request $request): Response
    {
        $filter = AdminUserFilter::fromQuery($request->query);
        $total = $this->getAdminUsers->count($filter);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, $request->query->getInt('page', 1)));

        return $this->render('admin/user/list.html.twig', [
            'users' => $this->getAdminUsers->filtered($filter, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'filter' => $filter,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ]);
    }
}
