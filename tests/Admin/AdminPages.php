<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin;

/**
 * Inventory of every admin page (PLAN §3.4) — the routes the new admin must provide.
 * Ids are the base fixture rows of tests/database/fixtures.sql.
 */
final class AdminPages
{
    /**
     * GET pages rendering HTML: url => expected <h1> (null = any non-empty heading, e.g. forms).
     *
     * @return array<string, null|string>
     */
    public static function htmlPages(): array
    {
        return [
            '/admin' => 'Přehled',
            '/admin/co-pripravit' => 'Co připravit',
            '/admin/pravidelne-objednavky' => 'Pravidelné objednávky',
            '/admin/statistiky' => 'Statistiky',
            '/admin/zmena-hesla' => 'Změna hesla',

            '/admin/aktuality' => 'Aktuality',
            '/admin/aktuality/pridat' => null,
            '/admin/aktuality/1' => 'Otevíráme novou prodejnu',

            '/admin/rozkrmena-krutata' => 'Rozkrmená krůťata',
            '/admin/rozkrmena-krutata/pridat' => null,
            '/admin/rozkrmena-krutata/1' => null,

            '/admin/chlazene-kruty' => 'Chlazené krůty',
            '/admin/chlazene-kruty/pridat' => null,
            '/admin/chlazene-kruty/1' => null,


            '/admin/produkty' => 'Maso a výrobky',
            '/admin/produkty/pridat' => null,
            '/admin/produkty/100' => null,
            '/admin/produkty/1' => null, // turkey product, reduced form

            '/admin/produkty/100/recepty' => 'Krůtí prsa',
            '/admin/produkty/100/recepty/pridat' => null,
            '/admin/produkty/100/recepty/1' => null,

            '/admin/sortiment' => 'Doplňkový sortiment',
            '/admin/sortiment/1' => 'Mouky',
            '/admin/sortiment/1/pridat' => null,
            '/admin/sortiment/1/1' => null,
            '/admin/sortiment/kategorie/pridat' => null,
            '/admin/sortiment/kategorie/1' => null,

            '/admin/texty' => 'Texty',
            '/admin/texty/1' => null,

            '/admin/prodejci' => 'Prodejci',
            '/admin/prodejci/pridat' => null,
            '/admin/prodejci/1' => null,

            '/admin/emaily' => 'Emaily',

            '/admin/prodejny' => 'Prodejny',
            '/admin/prodejny/pridat' => null,
            '/admin/prodejny/1' => null,
            '/admin/prodejny/zavreno/pridat' => null,

            '/admin/uzivatele' => 'Uživatelé',
            '/admin/uzivatele/2' => null,
            '/admin/uzivatele/2/zakazane-produkty' => null,

            '/admin/objednavky' => 'Objednávky',
            '/admin/objednavky/1' => null,
            '/admin/objednavky/2/boxxi' => null,
        ];
    }

    /**
     * Every GET url that must be protected (HTML pages + downloads).
     *
     * @return list<string>
     */
    public static function protectedGetUrls(): array
    {
        return [
            ...array_keys(self::htmlPages()),
            '/admin/objednavky/export.csv',
        ];
    }

    /**
     * POST-only mutating endpoints (PLAN §3.3, §4.6).
     *
     * @return list<string>
     */
    public static function mutatingUrls(): array
    {
        return [
            '/admin/record/actuality/1/deactivate',
            '/admin/record/actuality/1/activate',
            '/admin/record/actuality/1/delete',
            '/admin/record/vendor/1/move-up',
            '/admin/record/vendor/1/move-down',
            '/admin/record/actuality/activate-all',
            '/admin/uzivatele/2/velkoobchod',
            '/admin/objednavky/1/pohoda',
            '/admin/objednavky/pohoda',
            '/admin/objednavky/1/boxxi/zrusit',
        ];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function protectedGetUrlsProvider(): \Generator
    {
        foreach (self::protectedGetUrls() as $url) {
            yield $url => [$url];
        }
    }

    /**
     * @return \Generator<string, array{string, null|string}>
     */
    public static function htmlPagesProvider(): \Generator
    {
        foreach (self::htmlPages() as $url => $heading) {
            yield $url => [$url, $heading];
        }
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function mutatingUrlsProvider(): \Generator
    {
        foreach (self::mutatingUrls() as $url) {
            yield $url => [$url];
        }
    }
}
