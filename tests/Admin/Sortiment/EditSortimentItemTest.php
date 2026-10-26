<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Sortiment;

final class EditSortimentItemTest extends SortimentTestCase
{
    private const EDIT_PATH = '/admin/sortiment/4/2001';

    public function testEdits(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);

        $form = $this->formByButton($crawler);
        self::assertSame('Fixture sortiment', $this->fieldValue($form, 'sortiment[name]'));
        self::assertSame('<p>Fixture text</p>', $this->fieldValue($form, 'sortiment[text]'));

        $this->submitAdminForm($crawler, 'sortiment', ['name' => 'Upravený sortiment', 'text' => '<h2>Nový</h2><p>text</p>']);

        $this->assertRedirectsTo(self::categoryPath(self::FIXTURE_CATEGORY_ID), self::FIXTURE_ID);

        $row = $this->fetchRow('sortiment', self::FIXTURE_ID);
        self::assertSame('Upravený sortiment', $row['name']);
        self::assertSame('<h2>Nový</h2><p>text</p>', $row['text']);
        self::assertSame(self::FIXTURE_CATEGORY_ID, self::intValue($row['category_id']));
        self::assertSame(20010, self::intValue($row['order']), 'Edit must not touch the order.');
        self::assertUpdateAudit($row);

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }

    public function testSaveAndContinueStaysOnForm(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);
        $this->submitAdminForm($crawler, 'sortiment', ['name' => 'Pokračování'], self::SAVE_AND_CONTINUE);

        $this->assertRedirectsTo(self::EDIT_PATH);
        self::assertSame('Pokračování', $this->fetchRow('sortiment', self::FIXTURE_ID)['name']);
    }

    public function testUnknownIdRedirectsToCategory(): void
    {
        $this->client->request('GET', '/admin/sortiment/4/999999');

        $this->assertRedirectsTo(self::categoryPath(self::FIXTURE_CATEGORY_ID));
    }

    public function testNameRequired(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);
        $this->submitAdminForm($crawler, 'sortiment', ['name' => '']);

        $this->assertFormRejected();
        self::assertSame('Fixture sortiment', $this->fetchRow('sortiment', self::FIXTURE_ID)['name']);
    }
}
