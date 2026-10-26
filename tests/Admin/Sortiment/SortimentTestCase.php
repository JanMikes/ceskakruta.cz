<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Sortiment;

use CeskaKruta\Web\Tests\Admin\Calendar\AdminFormHelpers;
use CeskaKruta\Web\Tests\Support\AdminWebTestCase;

abstract class SortimentTestCase extends AdminWebTestCase
{
    use AdminFormHelpers;

    /** sortiment row in category 4 "Ostatní" (tests/database/fixtures/20-catalog-simple.sql) */
    protected const FIXTURE_ID = 2001;
    protected const FIXTURE_CATEGORY_ID = 4;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    protected static function categoryPath(int $categoryId): string
    {
        return '/admin/sortiment/' . $categoryId;
    }

    /**
     * Own empty category, so row positions (first 3 / last) are not affected by other fixtures.
     */
    protected function insertCategory(string $name = 'Testovací kategorie'): int
    {
        return $this->insert('sortiment_category', ['name' => $name, 'order' => 1]);
    }

    protected function insertItem(int $categoryId, int $order, string $name = 'Položka', bool $active = true): int
    {
        return $this->insert('sortiment', [
            'category_id' => $categoryId,
            'order' => $order,
            'name' => $name,
            'text' => '<p>' . $name . '</p>',
            'active_flag' => $active,
        ]);
    }
}
