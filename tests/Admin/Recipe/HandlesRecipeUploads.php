<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Recipe;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Upload helpers for recipe photo tests (PLAN §2.10, §3.9).
 * Files are not rolled back by DAMA → every file that appears under `uploads_dir` during a test is deleted afterwards,
 * temporary source files too.
 */
trait HandlesRecipeUploads
{
    /** @var list<string> */
    private array $uploadedFilesBefore = [];

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadedFilesBefore = $this->currentUploadedFiles();
    }

    protected function tearDown(): void
    {
        foreach (array_diff($this->currentUploadedFiles(), $this->uploadedFilesBefore) as $file) {
            @unlink($file);
        }

        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->uploadedFilesBefore = [];
        $this->temporaryFiles = [];

        parent::tearDown();
    }

    /**
     * Container parameter `uploads_dir` (PLAN §3.9, test env: %kernel.project_dir%/var/test-uploads).
     */
    protected function uploadsDir(): string
    {
        $container = self::getContainer();
        self::assertTrue($container->hasParameter(self::uploadsDirParameter()), 'Container parameter "uploads_dir" is not configured (PLAN §3.9).');

        $dir = $container->getParameter(self::uploadsDirParameter());
        self::assertIsString($dir);

        return rtrim($dir, '/');
    }

    /**
     * Where a `photo` row's file lives: `photo.path` is relative to `public/` and starts with `upload/`,
     * which is the uploads root (`uploads_dir`).
     */
    protected function photoFilePath(string $path, string $filename): string
    {
        self::assertStringStartsWith('upload/', $path);

        return $this->uploadsDir() . '/' . substr($path, strlen('upload/')) . '/' . $filename;
    }

    protected function assertNoNewUploadedFiles(): void
    {
        self::assertSame([], array_values(array_diff($this->currentUploadedFiles(), $this->uploadedFilesBefore)), 'No file must be stored.');
    }

    protected function createPngUpload(int $width, int $height, string $clientName = 'foto.png'): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        $color = imagecolorallocate($image, 200, 80, 40);
        self::assertNotFalse($color);
        imagefill($image, 0, 0, $color);

        $file = $this->temporaryFile();
        imagepng($image, $file);

        return new UploadedFile($file, $clientName, 'image/png', null, true);
    }

    protected function createFakeUpload(string $clientName, string $mimeType, string $content): UploadedFile
    {
        $file = $this->temporaryFile();
        file_put_contents($file, $content);

        return new UploadedFile($file, $clientName, $mimeType, null, true);
    }

    /**
     * Not a literal at the call sites: PHPStan resolves literal parameter names against the compiled container,
     * where `uploads_dir` does not exist until the feature is implemented.
     */
    private static function uploadsDirParameter(): string
    {
        return 'uploads_dir';
    }

    private function temporaryFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'recipe-upload-');
        self::assertIsString($file);
        $this->temporaryFiles[] = $file;

        return $file;
    }

    /**
     * @return list<string>
     */
    private function currentUploadedFiles(): array
    {
        $container = self::getContainer();

        if (!$container->hasParameter(self::uploadsDirParameter())) {
            return [];
        }

        $dir = $container->getParameter(self::uploadsDirParameter());

        if (!is_string($dir) || !is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
