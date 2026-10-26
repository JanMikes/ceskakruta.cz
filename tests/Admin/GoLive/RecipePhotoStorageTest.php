<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\GoLive;

use CeskaKruta\Web\Tests\Admin\Product\SubmitsAdminForms;
use CeskaKruta\Web\Tests\Admin\Recipe\HandlesRecipeUploads;
use CeskaKruta\Web\Tests\Support\AdminWebTestCase;

/**
 * PLAN §3.9: uploads root = container parameter `uploads_dir` (prod `public/upload`, test `var/test-uploads`);
 * files keep the old relative layout, so `photo.path = 'upload/recipes'` stays readable by the old app and the frontend.
 */
final class RecipePhotoStorageTest extends AdminWebTestCase
{
    use SubmitsAdminForms;
    use HandlesRecipeUploads;

    public function testUploadsDirParameterInTestEnvironment(): void
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');

        self::assertSame($projectDir . '/var/test-uploads', $this->uploadsDir());
    }

    public function testStoresUnderUploadsDirWithLegacyRelativePath(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001/recepty/3002');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, [], files: ['recipe' => ['image' => $this->createPngUpload(16, 12, 'stehno.jpg.png')]]);

        $this->assertRedirectsTo('/admin/produkty/3001/recepty', 3002);

        $recipe = $this->fetchRow('recipe', 3002);
        self::assertIsNumeric($recipe['photo_id']);
        $photo = $this->fetchRow('photo', $this->asInt($recipe['photo_id']));

        self::assertSame('upload/recipes', $photo['path']);
        self::assertIsString($photo['filename']);
        self::assertStringNotContainsString('/', $photo['filename'], 'filename is a bare file name, the directory is in photo.path');

        // public/{photo.path}/{photo.filename} in production ⇔ {uploads_dir}/recipes/{filename}
        $file = $this->photoFilePath('upload/recipes', $photo['filename']);
        self::assertSame($this->uploadsDir() . '/recipes/' . $photo['filename'], $file);
        self::assertFileExists($file);
    }
}
