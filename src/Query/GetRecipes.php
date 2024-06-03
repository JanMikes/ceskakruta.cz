<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Value\Recipe;
use Doctrine\DBAL\Connection;

final class GetRecipes
{
    /** @var array<Recipe>|null  */
    private null|array $recipes = null;

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    /**
     * @return array<Recipe>
     */
    public function all(): array
    {
        if ($this->recipes === null) {
            $rows = $this->connection
                ->executeQuery('SELECT * FROM recipe WHERE active_flag = 1 AND del_flag = 0 ORDER BY id')
                ->fetchAllAssociative();

            $recipes = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     id: int,
                 *     product_id: int,
                 *     name: string,
                 *     text: null|string,
                 * } $row
                 */

                $id = $row['id'];

                $recipes[$id] = new Recipe(
                    id: $id,
                    productId: $row['product_id'],
                    name: $row['name'],
                    text: $row['text'] ?? '',
                );
            }

            $this->recipes = $recipes;
        }

        return $this->recipes;
    }

    public function oneById(int $recipeId): Recipe
    {
        return $this->all()[$recipeId] ?? throw new \Exception('Recipe not found');
    }
}
