<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Value\Recipe;
use Doctrine\DBAL\Connection;

final class GetRecipes
{
    /** @var array<Recipe>|null  */
    private null|array $recipes = null;

    /** @var array<int, array<Recipe>>|null  */
    private null|array $recipesPerProduct = null;

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
            $this->recipesPerProduct = [];

            $query = <<<SQL
SELECT recipe.*, photo.filename, photo.path
FROM recipe
LEFT JOIN photo ON photo.id = recipe.photo_id
WHERE recipe.active_flag = 1 AND recipe.del_flag = 0
ORDER BY photo_id IS NULL, name
SQL;
            $rows = $this->connection
                ->executeQuery($query)
                ->fetchAllAssociative();

            $recipes = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     id: int,
                 *     product_id: int,
                 *     name: string,
                 *     text: null|string,
                 *     filename: null|string,
                 *     path: null|string,
                 * } $row
                 */

                $id = $row['id'];
                $recipe = new Recipe(
                    id: $id,
                    productId: $row['product_id'],
                    name: $row['name'],
                    text: $row['text'] ?? '',
                    filename: $row['filename'],
                    path: $row['path'],
                );

                $recipes[$id] = $recipe;
                $this->recipesPerProduct[$row['product_id']][] = $recipe;
            }

            $this->recipes = $recipes;
        }

        return $this->recipes;
    }

    public function oneById(int|string $recipeId): Recipe
    {
        return $this->all()[$recipeId] ?? throw new \Exception('Recipe not found');
    }

    public function getCountForProduct(int $productId): int
    {
        if ($this->recipesPerProduct === null) {
            // It initializes everything
            $this->all();
        }

        assert($this->recipesPerProduct !== null);

        return count($this->recipesPerProduct[$productId] ?? []);
    }

    /**
     * @return array<Recipe>
     */
    public function getForProduct(int $productId): array
    {
        if ($this->recipesPerProduct === null) {
            // It initializes everything
            $this->all();
        }

        return $this->recipesPerProduct[$productId] ?? [];
    }
}
