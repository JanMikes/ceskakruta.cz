<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Vendor;

final class EditVendorTest extends VendorTestCase
{
    private const EDIT_PATH = '/admin/prodejci/2001';

    public function testEdits(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);

        $form = $this->formByButton($crawler);
        self::assertSame('Fixture prodejce', $this->fieldValue($form, 'vendor[name]'));
        self::assertSame('Brno', $this->fieldValue($form, 'vendor[city]'));
        self::assertSame('60200', $this->fieldValue($form, 'vendor[zip]'));
        self::assertSame('fixture-vendor@example.test', $this->fieldValue($form, 'vendor[email]'));

        $values = [
            'place' => 'Praha',
            'name' => 'Upravený prodejce',
            'address' => 'Dlouhá 10',
            'city' => 'Praha',
            'zip' => '11000',
            'email' => '',
            'web' => 'https://upraveny.example.test',
            'phone' => '+420777777777',
        ];
        $this->submitAdminForm($crawler, 'vendor', $values);

        $this->assertRedirectsTo(self::LIST_PATH, self::FIXTURE_ID);

        $row = $this->fetchRow('vendor', self::FIXTURE_ID);
        foreach ($values as $column => $value) {
            if ($value === '') {
                self::assertContains($row[$column], ['', null], sprintf('Column "%s".', $column));
            } else {
                self::assertSame($value, $row[$column], sprintf('Column "%s".', $column));
            }
        }
        self::assertSame(20010, self::intValue($row['order']), 'Edit must not touch the order.');
        self::assertUpdateAudit($row);

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }

    public function testEmailValidation(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);
        $this->submitAdminForm($crawler, 'vendor', ['email' => 'not-an-email']);

        $this->assertFormRejected();
        self::assertSame('fixture-vendor@example.test', $this->fetchRow('vendor', self::FIXTURE_ID)['email']);
    }

    public function testSaveAndContinueStaysOnForm(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);
        $this->submitAdminForm($crawler, 'vendor', ['name' => 'Pokračování'], self::SAVE_AND_CONTINUE);

        $this->assertRedirectsTo(self::EDIT_PATH);
        self::assertSame('Pokračování', $this->fetchRow('vendor', self::FIXTURE_ID)['name']);
    }

    public function testUnknownIdRedirectsToList(): void
    {
        $this->client->request('GET', '/admin/prodejci/999999');

        $this->assertRedirectsTo(self::LIST_PATH);
    }
}
