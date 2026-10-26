<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Content;

/**
 * PLAN §2.12: edit form has only `text` (WYSIWYG); `code` (read by the frontend ContentService) and `name` stay untouched.
 * §3.12: the HTML is stored exactly as submitted (raw HTML mode of the editor) — no server-side sanitising.
 */
final class EditContentTest extends ContentTestCase
{
    private const EDIT_PATH = '/admin/texty/2001';

    public function testOnlyTextIsEditable(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);

        $form = $this->formByButton($crawler);
        self::assertTrue($form->has('content[text]'));
        self::assertSame('<p>Původní text</p>', $this->fieldValue($form, 'content[text]'));
        foreach (['code', 'name', 'order'] as $field) {
            self::assertFalse($form->has('content[' . $field . ']'), sprintf('Field "%s" must not be editable.', $field));
        }

        // the section name is shown (old h1 "Editovat text - {name}")
        self::assertSelectorTextContains('h1', 'Editovatelná sekce');

        $this->submitAdminForm($crawler, 'content', ['text' => '<p>Nový text</p>']);

        $this->assertRedirectsTo(self::LIST_PATH, self::FIXTURE_ID);

        $row = $this->fetchRow('content', self::FIXTURE_ID);
        self::assertSame('<p>Nový text</p>', $row['text']);
        self::assertSame('test_editable', $row['code']);
        self::assertSame('Editovatelná sekce', $row['name']);
        self::assertSame(20010, self::intValue($row['order']));
        self::assertUpdateAudit($row);

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }

    public function testRawHtmlModeSavesAsIs(): void
    {
        // legacy content as found in production (§3.12): inline styles, <font>, align=, base64 image, link target
        $html = '<h2 style="color: rgb(88, 88, 90);">Nadpis</h2>' . "\n"
            . '<p align="center"><font face="Helvetica">Starý</font> <span class="small">menší</span></p>'
            . '<p><img src="data:image/png;base64,iVBORw0KGgo=" alt=""><a href="https://example.test" target="_blank">odkaz</a></p>'
            . '<script>/* kept as-is, admin only */</script>';

        $crawler = $this->openPage(self::EDIT_PATH);
        $this->submitAdminForm($crawler, 'content', ['text' => $html]);

        $this->assertRedirectsTo(self::LIST_PATH, self::FIXTURE_ID);
        self::assertSame($html, $this->fetchRow('content', self::FIXTURE_ID)['text']);
    }

    public function testEmptyTextIsAllowed(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);
        $this->submitAdminForm($crawler, 'content', ['text' => '']);

        $this->assertRedirectsTo(self::LIST_PATH, self::FIXTURE_ID);
        self::assertContains($this->fetchRow('content', self::FIXTURE_ID)['text'], ['', null]);
    }

    public function testSaveAndContinueStaysOnForm(): void
    {
        $crawler = $this->openPage(self::EDIT_PATH);
        $this->submitAdminForm($crawler, 'content', ['text' => '<p>Pokračování</p>'], self::SAVE_AND_CONTINUE);

        $this->assertRedirectsTo(self::EDIT_PATH);
        self::assertSame('<p>Pokračování</p>', $this->fetchRow('content', self::FIXTURE_ID)['text']);
    }

    public function testUnknownIdRedirectsToList(): void
    {
        $this->client->request('GET', '/admin/texty/999999');

        $this->assertRedirectsTo(self::LIST_PATH);
    }
}
