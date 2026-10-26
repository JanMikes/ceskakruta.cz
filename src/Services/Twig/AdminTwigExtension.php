<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Twig;

use CeskaKruta\Web\Query\Admin\GetSortimentCategories;
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
        ];
    }

    /**
     * @return list<SortimentCategory>
     */
    public function sortimentCategories(): array
    {
        return $this->getSortimentCategories->all();
    }
}
