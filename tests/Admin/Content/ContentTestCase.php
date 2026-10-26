<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Content;

use CeskaKruta\Web\Tests\Admin\Calendar\AdminFormHelpers;
use CeskaKruta\Web\Tests\Support\AdminWebTestCase;

abstract class ContentTestCase extends AdminWebTestCase
{
    use AdminFormHelpers;

    /** tests/database/fixtures/20-catalog-simple.sql */
    protected const FIXTURE_ID = 2001;
    protected const LIST_PATH = '/admin/texty';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }
}
