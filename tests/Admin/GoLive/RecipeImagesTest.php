<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\GoLive;

use CeskaKruta\Web\Tests\Support\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PLAN §1.3.1 / §3.9: recipe photos are copied to `public/upload/recipes/` and served by this app,
 * the frontend must not hot-link them from old.ceskakruta.cz any more.
 */
final class RecipeImagesTest extends WebTestCase
{
    public function testRecipeImagesAreServedLocally(): void
    {
        $templates = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 3) . '/templates', \FilesystemIterator::SKIP_DOTS));
        $referencing = [];

        foreach ($templates as $template) {
            if (!$template instanceof \SplFileInfo || !$template->isFile()) {
                continue;
            }

            $content = file_get_contents($template->getPathname());
            self::assertIsString($content);

            if (str_contains($content, 'old.ceskakruta.cz')) {
                $referencing[] = $template->getFilename();
            }
        }

        self::assertSame([], $referencing, 'Templates still referencing old.ceskakruta.cz');
    }

    /**
     * Fixture recipe 3001 has photo `upload/recipes/5f0c1a2b3c4d5-3001.jpg` (tests/database/fixtures/30-products.sql).
     */
    #[DataProvider('provideRecipePages')]
    public function testFrontendRendersLocalRecipeImage(string $url): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', $url);

        self::assertResponseIsSuccessful();

        $sources = $crawler->filter('img')->each(static fn ($image): string => (string) $image->attr('src'));
        self::assertContains('/upload/recipes/5f0c1a2b3c4d5-3001.jpg', $sources);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideRecipePages(): iterable
    {
        yield 'recipes list' => ['/recepty'];
        yield 'recipe detail' => ['/recept/3001'];
    }
}
