<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Recipe;

use CeskaKruta\Web\Exceptions\InvalidRecipePhoto;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Recipe\CreateRecipe;
use CeskaKruta\Web\Query\Admin\GetAdminProducts;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use CeskaKruta\Web\Services\Admin\RecipePhotoStorage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateRecipeHandler
{
    public function __construct(
        private GetAdminProducts $getAdminProducts,
        private AdminRecordRepository $repository,
        private RecipePhotoStorage $photoStorage,
    ) {
    }

    /**
     * @return int id of the new recipe
     * @throws RecordNotFound
     * @throws InvalidRecipePhoto
     */
    public function __invoke(CreateRecipe $message): int
    {
        $this->getAdminProducts->oneById($message->productId);

        $id = $this->repository->insert('recipe', $message->columns(), $message->adminId);

        if ($message->photo !== null) {
            $photoId = $this->photoStorage->store($message->photo, $id, $message->adminId);
            $this->repository->update('recipe', $id, ['photo_id' => $photoId], $message->adminId);
        }

        return $id;
    }
}
