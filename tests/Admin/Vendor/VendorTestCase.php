<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Vendor;

use CeskaKruta\Web\Tests\Admin\Calendar\AdminFormHelpers;
use CeskaKruta\Web\Tests\Support\AdminWebTestCase;

abstract class VendorTestCase extends AdminWebTestCase
{
    use AdminFormHelpers;

    /** tests/database/fixtures/20-catalog-simple.sql */
    protected const FIXTURE_ID = 2001;
    protected const LIST_PATH = '/admin/prodejci';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    /**
     * @return array<string, string>
     */
    protected static function validValues(): array
    {
        return [
            'place' => 'Olomouc',
            'name' => 'Nový prodejce',
            'address' => 'Náměstí 3',
            'city' => 'Olomouc',
            'zip' => '77900',
            'email' => 'novy-prodejce@example.test',
            'web' => 'https://novy-prodejce.example.test',
            'phone' => '+420666666666',
        ];
    }

    /**
     * @param array<string, string> $override
     */
    protected function assertCreateOutcome(array $override, bool $valid): void
    {
        $before = $this->countRows('vendor');

        $crawler = $this->openPage(self::LIST_PATH . '/pridat');
        $this->submitAdminForm($crawler, 'vendor', $override + static::validValues());

        if ($valid) {
            $this->assertRedirectsTo(self::LIST_PATH, $this->lastInsertedId('vendor'));
            self::assertSame($before + 1, $this->countRows('vendor'));
        } else {
            $this->assertFormRejected();
            self::assertSame($before, $this->countRows('vendor'));
        }
    }
}
