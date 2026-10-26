<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Admin;

use CeskaKruta\Web\Exceptions\InvalidRecipePhoto;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stores recipe photos like the old `ManageRecipeFormFactory` (PLAN §2.10, §3.9):
 * file `{uploads_dir}/recipes/{uniqid}-{recipeId}.{ext}` + `photo` row with `path = 'upload/recipes'` (relative to public/).
 * The client file name is never used, the extension comes from the detected MIME type.
 */
readonly final class RecipePhotoStorage
{
    private const PATH = 'upload/recipes';
    private const DIRECTORY = 'recipes';

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    ];

    public function __construct(
        #[Autowire('%uploads_dir%')]
        private string $uploadsDir,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @return int id of the new `photo` row
     * @throws InvalidRecipePhoto
     */
    public function store(UploadedFile $file, int $recipeId, int $adminId): int
    {
        $extension = self::EXTENSIONS[(string) $file->getMimeType()] ?? throw new InvalidRecipePhoto();

        $size = @getimagesize($file->getPathname());
        if ($size === false) {
            throw new InvalidRecipePhoto();
        }

        $filename = sprintf('%s-%d.%s', uniqid(), $recipeId, $extension);
        $stored = $file->move(rtrim($this->uploadsDir, '/') . '/' . self::DIRECTORY, $filename);

        try {
            return $this->repository->insert('photo', [
                'filename' => $filename,
                'path' => self::PATH,
                'width' => $size[0],
                'height' => $size[1],
            ], $adminId);
        } catch (\Throwable $exception) {
            @unlink($stored->getPathname());

            throw $exception;
        }
    }
}
