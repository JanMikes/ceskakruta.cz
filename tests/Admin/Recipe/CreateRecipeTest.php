<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Recipe;

use CeskaKruta\Web\Tests\Admin\Product\SubmitsAdminForms;
use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PLAN §2.10 form: name (required, max 50), image "Foto" (optional, JPG/GIF/PNG), text.
 * Upload → `uploads_dir/recipes/{uniqid}-{recipeId}.{ext}`, `photo` row (path 'upload/recipes'), `recipe.photo_id`.
 */
final class CreateRecipeTest extends AdminWebTestCase
{
    use SubmitsAdminForms;
    use HandlesRecipeUploads;

    public function testCreatesWithoutPhoto(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3002/recepty/pridat');
        self::assertResponseIsSuccessful();

        $photosBefore = $this->fetchInt('SELECT COUNT(*) FROM photo');

        $this->submitAdminForm($crawler, [
            'recipe[name]' => 'Klobása na grilu',
            'recipe[text]' => '<p>Grilujeme klobásu</p>',
        ]);

        $id = $this->lastInsertedId('recipe');
        $this->assertRedirectsTo('/admin/produkty/3002/recepty', $id);

        $row = $this->fetchRow('recipe', $id);
        self::assertSame(3002, $this->asInt($row['product_id']));
        self::assertSame('Klobása na grilu', $row['name']);
        self::assertSame('<p>Grilujeme klobásu</p>', $row['text']);
        self::assertNull($row['photo_id']);
        self::assertSame(1, $this->asInt($row['active_flag']));
        self::assertSame(0, $this->asInt($row['del_flag']));
        self::assertSame('admin', $row['ins_process']);
        self::assertSame((string) self::ADMIN_ID, $row['ins_user_id']);
        self::assertSame($photosBefore, $this->fetchInt('SELECT COUNT(*) FROM photo'));

        $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
        self::assertSelectorExists('#item-' . $id);
    }

    public function testCreatesWithPhoto(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3002/recepty/pridat');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[type="file"][name="recipe[image]"]');

        $this->submitAdminForm($crawler, [
            'recipe[name]' => 'Klobása s fotkou',
            'recipe[text]' => '<p>Text</p>',
        ], files: ['recipe' => ['image' => $this->createPngUpload(40, 30, 'Klobasa.PNG')]]);

        $id = $this->lastInsertedId('recipe');
        $this->assertRedirectsTo('/admin/produkty/3002/recepty', $id);

        $recipe = $this->fetchRow('recipe', $id);
        self::assertNotNull($recipe['photo_id']);
        self::assertIsNumeric($recipe['photo_id']);

        $photo = $this->fetchRow('photo', $this->asInt($recipe['photo_id']));
        self::assertSame('upload/recipes', $photo['path']);
        self::assertIsString($photo['filename']);
        self::assertMatchesRegularExpression(sprintf('~^[0-9a-f]+-%d\.png$~', $id), $photo['filename'], 'Filename {uniqid}-{recipeId}.{lowercase ext}');
        self::assertSame(40, $this->asInt($photo['width']));
        self::assertSame(30, $this->asInt($photo['height']));
        self::assertSame(1, $this->asInt($photo['active_flag']));
        self::assertSame(0, $this->asInt($photo['del_flag']));
        self::assertSame('admin', $photo['ins_process']);

        $file = $this->uploadsDir() . '/recipes/' . $photo['filename'];
        self::assertFileExists($file);
        $size = getimagesize($file);
        self::assertIsArray($size);
        self::assertSame([40, 30], [$size[0], $size[1]]);
    }

    #[DataProvider('provideNonImages')]
    public function testRejectsNonImage(string $clientName, string $mimeType, string $content): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3002/recepty/pridat');
        self::assertResponseIsSuccessful();

        $recipesBefore = $this->fetchInt('SELECT COUNT(*) FROM recipe');
        $photosBefore = $this->fetchInt('SELECT COUNT(*) FROM photo');

        $this->submitAdminForm($crawler, [
            'recipe[name]' => 'Recept s neplatným souborem',
            'recipe[text]' => '<p>Text</p>',
        ], files: ['recipe' => ['image' => $this->createFakeUpload($clientName, $mimeType, $content)]]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::assertSame($recipesBefore, $this->fetchInt('SELECT COUNT(*) FROM recipe'));
        self::assertSame($photosBefore, $this->fetchInt('SELECT COUNT(*) FROM photo'));
        $this->assertNoNewUploadedFiles();
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideNonImages(): iterable
    {
        yield 'text file' => ['recept.txt', 'text/plain', 'Toto není obrázek'];
        yield 'text pretending to be png' => ['recept.png', 'image/png', 'Toto není obrázek'];
        yield 'pdf' => ['recept.pdf', 'application/pdf', "%PDF-1.4\n%âãÏÓ\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"];
    }

    #[DataProvider('provideInvalidNames')]
    public function testValidation(string $name): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3002/recepty/pridat');
        self::assertResponseIsSuccessful();

        $recipesBefore = $this->fetchInt('SELECT COUNT(*) FROM recipe');

        $this->submitAdminForm($crawler, ['recipe[name]' => $name, 'recipe[text]' => '<p>Text</p>']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::assertSame($recipesBefore, $this->fetchInt('SELECT COUNT(*) FROM recipe'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidNames(): iterable
    {
        yield 'empty' => [''];
        yield 'too long' => [str_repeat('a', 51)];
    }

    public function testUnknownProductRedirects(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/produkty/3999/recepty/pridat');

        $this->assertRedirectsTo('/admin/produkty');
    }
}
