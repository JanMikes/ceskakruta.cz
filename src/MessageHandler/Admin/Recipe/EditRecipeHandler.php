<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Recipe;

use CeskaKruta\Web\Exceptions\InvalidRecipePhoto;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Recipe\EditRecipe;
use CeskaKruta\Web\Query\Admin\GetAdminRecipes;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use CeskaKruta\Web\Services\Admin\RecipePhotoStorage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditRecipeHandler
{
    public function __construct(
        private GetAdminRecipes $getAdminRecipes,
        private AdminRecordRepository $repository,
        private RecipePhotoStorage $photoStorage,
    ) {
    }

    /**
     * @throws RecordNotFound
     * @throws InvalidRecipePhoto
     */
    public function __invoke(EditRecipe $message): void
    {
        $this->getAdminRecipes->oneById($message->id);

        $columns = $message->columns();

        // The previous photo row and file are kept, like in the old app
        if ($message->photo !== null) {
            $columns['photo_id'] = $this->photoStorage->store($message->photo, $message->id, $message->adminId);
        }

        $this->repository->update('recipe', $message->id, $columns, $message->adminId);
    }
}
