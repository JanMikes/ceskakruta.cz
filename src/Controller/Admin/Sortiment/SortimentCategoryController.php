<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller\Admin\Sortiment;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Query\Admin\CountInactiveRecords;
use CeskaKruta\Web\Query\Admin\GetAdminSortiment;
use CeskaKruta\Web\Query\Admin\GetSortimentCategories;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SortimentCategoryController extends AbstractController
{
    public function __construct(
        readonly private GetSortimentCategories $getSortimentCategories,
        readonly private GetAdminSortiment $getAdminSortiment,
        readonly private CountInactiveRecords $countInactiveRecords,
    ) {
    }

    #[Route(path: '/admin/sortiment/{categoryId}', name: 'admin_sortiment_category', requirements: ['categoryId' => '\d+'])]
    public function __invoke(int $categoryId): Response
    {
        try {
            $category = $this->getSortimentCategories->oneById($categoryId);
        } catch (RecordNotFound) {
            return $this->redirectToRoute('admin_sortiment');
        }

        return $this->render('admin/sortiment/list.html.twig', [
            'category' => $category,
            'items' => $this->getAdminSortiment->allInCategory($categoryId),
            'inactive_count' => $this->countInactiveRecords->count(AdminTable::Sortiment, $categoryId),
        ]);
    }
}
