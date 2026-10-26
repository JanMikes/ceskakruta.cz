<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Twig;

use CeskaKruta\Web\Query\Admin\GetSortimentCategories;
use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use CeskaKruta\Web\Value\Admin\Grid\GridAction;
use CeskaKruta\Web\Value\Admin\SortimentCategory;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AdminTwigExtension extends AbstractExtension
{
    public function __construct(
        readonly private GetSortimentCategories $getSortimentCategories,
    ) {
    }

    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_sortiment_categories', $this->sortimentCategories(...)),
            new TwigFunction('admin_grid_allows', $this->gridAllows(...)),
        ];
    }

    /**
     * @return list<SortimentCategory>
     */
    public function sortimentCategories(): array
    {
        return $this->getSortimentCategories->all();
    }

    /**
     * Same rules as the grid handlers, so a hidden action is exactly a refused one.
     *
     * @param array<string, mixed> $row columns the rules need (`id`, product `type`)
     */
    public function gridAllows(string $table, string $action, array $row): bool
    {
        return AdminTable::from($table)->allows(GridAction::from($action), $row);
    }
}
