<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Sortiment;

/**
 * Category form: `name` only (required, max 255), created with `order = id * 10` like the other orderable lists.
 */
final class SortimentCategoryFormTest extends SortimentTestCase
{
    private const CREATE_PATH = '/admin/sortiment/kategorie/pridat';
    private const EDIT_PATH = '/admin/sortiment/kategorie/2';

    public function testCreates(): void
    {
        $crawler = $this->openPage(self::CREATE_PATH);
        $this->submitAdminForm($crawler, 'sortiment_category', ['name' => 'Sirupy']);

        $id = $this->lastInsertedId('sortiment_category');
        $this->assertRedirectsTo('/admin/sortiment', $id);

        $row = $this->fetchRow('sortiment_category', $id);
        self::assertSame('Sirupy', $row['name']);
        self::assertSame($id * 10, self::intValue($row['order']));
        self::assertSame(1, self::intValue($row['active_flag']));
        self::assertInsertAudit($row);

        $crawler = $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
        self::assertContains($id, $this->listedIds($crawler));

        // The new category opens its (empty) items list
        $this->openPage('/admin/sortiment/' . $id);
    }

    public function testEdits(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);
        self::assertSame('Oleje', $this->fieldValue($this->formByButton($crawler), 'sortiment_category[name]'));

        $this->submitAdminForm($crawler, 'sortiment_category', ['name' => 'Oleje a octy']);

        $this->assertRedirectsTo('/admin/sortiment', 2);
        $row = $this->fetchRow('sortiment_category', 2);
        self::assertSame('Oleje a octy', $row['name']);
        self::assertSame(30, self::intValue($row['order']), 'Edit must not touch the order.');
        self::assertUpdateAudit($row);
    }

    public function testSaveAndContinueStaysOnForm(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);
        $this->submitAdminForm($crawler, 'sortiment_category', ['name' => 'Pokračování'], self::SAVE_AND_CONTINUE);

        $this->assertRedirectsTo(self::EDIT_PATH);
        self::assertSame('Pokračování', $this->fetchRow('sortiment_category', 2)['name']);
    }

    public function testNameRequired(): void
    {
        $before = $this->countRows('sortiment_category');

        $crawler = $this->openPage(self::CREATE_PATH);
        $this->submitAdminForm($crawler, 'sortiment_category', ['name' => '']);

        $this->assertFormRejected();
        self::assertSame($before, $this->countRows('sortiment_category'));

        $crawler = $this->openPage(self::EDIT_PATH);
        $this->submitAdminForm($crawler, 'sortiment_category', ['name' => '   ']);

        $this->assertFormRejected();
        self::assertSame('Oleje', $this->fetchRow('sortiment_category', 2)['name']);
    }

    public function testUnknownOrDeletedIdRedirectsToIndex(): void
    {
        $this->client->request('GET', '/admin/sortiment/kategorie/999999');
        $this->assertRedirectsTo('/admin/sortiment');

        $deleted = $this->insert('sortiment_category', ['name' => 'Smazaná', 'del_flag' => 1]);
        $this->client->request('GET', '/admin/sortiment/kategorie/' . $deleted);
        $this->assertRedirectsTo('/admin/sortiment');
    }
}
