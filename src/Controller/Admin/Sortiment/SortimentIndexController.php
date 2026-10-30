<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Sortiment;

use CeskaKruta\Web\Query\Admin\CountInactiveRecords;
use CeskaKruta\Web\Query\Admin\GetSortimentCategories;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SortimentIndexController extends AbstractController
{
    public function __construct(
        readonly private GetSortimentCategories $getSortimentCategories,
        readonly private CountInactiveRecords $countInactiveRecords,
    ) {
    }

    #[Route(path: '/admin/sortiment', name: 'admin_sortiment')]
    public function __invoke(): Response
    {
        return $this->render('admin/sortiment/index.html.twig', [
            'categories' => $this->getSortimentCategories->all(),
            'inactive_count' => $this->countInactiveRecords->count(AdminTable::SortimentCategory),
        ]);
    }
}
