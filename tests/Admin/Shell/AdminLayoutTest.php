<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Shell;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * PLAN §2.18 — navigation in the order of the old navbar.
 */
final class AdminLayoutTest extends AdminWebTestCase
{
    private const SECTIONS = [
        'Aktuality' => '/admin/aktuality',
        'Rozkrmená krůťata' => '/admin/rozkrmena-krutata',
        'Chlazené krůty' => '/admin/chlazene-kruty',
        'Maso a výrobky' => '/admin/produkty',
        'Doplňkový sortiment' => null, // dropdown with categories
        'Texty' => '/admin/texty',
        'Prodejci' => '/admin/prodejci',
        'Emaily' => '/admin/emaily',
        'Prodejny' => '/admin/prodejny',
        'Uživatelé' => '/admin/uzivatele',
        'Objednávky' => '/admin/objednavky',
    ];

    public function testNavigationContainsAllSectionsInOrder(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();

        $labels = $crawler->filter('nav a, nav button')->each(static fn (Crawler $node): string => trim($node->text()));
        $sectionLabels = array_values(array_filter($labels, static fn (string $label): bool => array_key_exists($label, self::SECTIONS)));

        self::assertSame(array_keys(self::SECTIONS), $sectionLabels);

        foreach (self::SECTIONS as $label => $url) {
            if ($url !== null) {
                self::assertCount(1, $crawler->filter(sprintf('nav a[href="%s"]', $url)), sprintf('Navigation link "%s" → %s', $label, $url));
            }
        }
    }

    public function testSortimentDropdownListsCategories(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();

        foreach ([1 => 'Mouky', 2 => 'Oleje', 3 => 'Koření', 4 => 'Ostatní'] as $categoryId => $name) {
            $link = $crawler->filter(sprintf('nav a[href="/admin/sortiment/%d"]', $categoryId));
            self::assertCount(1, $link);
            self::assertSame($name, trim($link->text()));
        }
    }

    public function testShowsLoggedAdminLinkingToPasswordChangeAndLogout(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('nav a[href="/admin/zmena-hesla"]', 'Admin Testovací');
        self::assertSelectorTextSame('nav a[href="/admin/logout"]', 'Odhlásit');
    }

    public function testDashboard(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Přehled');
        self::assertPageTitleContains('Administrace');
    }
}
