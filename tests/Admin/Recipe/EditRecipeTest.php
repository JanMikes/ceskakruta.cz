<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Recipe;

use CeskaKruta\Web\Tests\Admin\Product\SubmitsAdminForms;
use CeskaKruta\Web\Tests\Support\AdminWebTestCase;

/**
 * PLAN §2.10 edit: name, image (current preview, optional replacement), text.
 */
final class EditRecipeTest extends AdminWebTestCase
{
    use SubmitsAdminForms;
    use HandlesRecipeUploads;

    public function testEdits(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001/recepty/3002');
        self::assertResponseIsSuccessful();

        self::assertSame('Dušené stehno', $this->fieldValue($crawler, 'recipe[name]'));
        self::assertStringContainsString('Dusíme', $this->fieldValue($crawler, 'recipe[text]'));

        $this->submitAdminForm($crawler, [
            'recipe[name]' => 'Dušené stehno na víně',
            'recipe[text]' => '<p>Dusíme na víně</p>',
        ]);

        $this->assertRedirectsTo('/admin/produkty/3001/recepty', 3002);

        $row = $this->fetchRow('recipe', 3002);
        self::assertSame('Dušené stehno na víně', $row['name']);
        self::assertSame('<p>Dusíme na víně</p>', $row['text']);
        self::assertSame(3001, $this->asInt($row['product_id']));
        self::assertNull($row['photo_id']);
        self::assertSame('admin', $row['upd_process']);
        self::assertSame((string) self::ADMIN_ID, $row['upd_user_id']);

        $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
    }

    public function testSaveAndContinueStaysOnForm(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001/recepty/3002');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, ['recipe[name]' => 'Dušené stehno 2'], button: 'Uložit a pokračovat v editaci');

        $this->assertRedirectsTo('/admin/produkty/3001/recepty/3002');
        self::assertSame('Dušené stehno 2', $this->fetchRow('recipe', 3002)['name']);
    }

    public function testUnknownIdRedirectsToList(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/produkty/3001/recepty/3999');

        $this->assertRedirectsTo('/admin/produkty/3001/recepty');
    }

    public function testUnknownProductRedirects(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/produkty/3999/recepty/3001');

        $this->assertRedirectsTo('/admin/produkty');
    }

    public function testShowsCurrentPhoto(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001/recepty/3001');
        self::assertResponseIsSuccessful();

        $images = $crawler->filter('form img');
        self::assertCount(1, $images, 'Current photo preview');
        self::assertStringEndsWith('/upload/recipes/5f0c1a2b3c4d5-3001.jpg', (string) $images->attr('src'));
    }

    public function testReplacesPhoto(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001/recepty/3001');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, [], files: ['recipe' => ['image' => $this->createPngUpload(20, 10, 'nove.png')]]);

        $this->assertRedirectsTo('/admin/produkty/3001/recepty', 3001);

        $recipe = $this->fetchRow('recipe', 3001);
        self::assertIsNumeric($recipe['photo_id']);
        self::assertNotSame(3001, $this->asInt($recipe['photo_id']));
        self::assertSame('Pečené stehno', $recipe['name'], 'Other fields keep their values');

        $photo = $this->fetchRow('photo', $this->asInt($recipe['photo_id']));
        self::assertSame('upload/recipes', $photo['path']);
        self::assertIsString($photo['filename']);
        self::assertMatchesRegularExpression('~^[0-9a-f]+-3001\.png$~', $photo['filename']);
        self::assertSame(20, $this->asInt($photo['width']));
        self::assertSame(10, $this->asInt($photo['height']));
        self::assertFileExists($this->uploadsDir() . '/recipes/' . $photo['filename']);
    }

    public function testKeepsPhotoWhenNoUpload(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001/recepty/3001');
        self::assertResponseIsSuccessful();

        $photosBefore = $this->fetchInt('SELECT COUNT(*) FROM photo');

        $this->submitAdminForm($crawler, ['recipe[name]' => 'Pečené stehno s bylinkami']);

        $this->assertRedirectsTo('/admin/produkty/3001/recepty', 3001);

        $recipe = $this->fetchRow('recipe', 3001);
        self::assertSame('Pečené stehno s bylinkami', $recipe['name']);
        self::assertSame(3001, $this->asInt($recipe['photo_id']));
        self::assertSame($photosBefore, $this->fetchInt('SELECT COUNT(*) FROM photo'));
        $this->assertNoNewUploadedFiles();
    }

    public function testRejectsNonImageAndKeepsPhoto(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001/recepty/3001');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, ['recipe[name]' => 'Nemá se uložit'], files: [
            'recipe' => ['image' => $this->createFakeUpload('recept.png', 'image/png', 'Toto není obrázek')],
        ]);

        self::assertResponseStatusCodeSame(422);
        $recipe = $this->fetchRow('recipe', 3001);
        self::assertSame('Pečené stehno', $recipe['name']);
        self::assertSame(3001, $this->asInt($recipe['photo_id']));
        $this->assertNoNewUploadedFiles();
    }
}
