<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Shared by calendar_cold and calendar_alive create tests (old ManageCalendarCold/AliveFormFactory):
 * `product_tp_id` required select from `lst_product_tp` with prompt "-- Vyberte --", `weight_from` / `weight_to` required floats.
 */
trait ProductTypeCalendarCreateTests
{
    public function testProductTypeRequired(): void
    {
        $this->assertCreateOutcome(['product_tp_id' => ''], false);
    }

    public function testProductTypeSelectOptions(): void
    {
        $crawler = $this->openPage(self::path() . '/pridat');

        $options = $crawler->filter(sprintf('select[name="%s[product_tp_id]"] option', self::table()));
        self::assertSame(
            ['' => '-- Vyberte --', '1' => 'Krůta', '2' => 'Krocan'],
            array_combine(
                $options->each(static fn (Crawler $option): string => (string) $option->attr('value')),
                $options->each(static fn (Crawler $option): string => trim($option->text())),
            ),
        );
    }

    public function testDeletedProductTypeIsNotOffered(): void
    {
        $deleted = $this->insert('lst_product_tp', ['name' => 'Smazaný typ', 'del_flag' => 1]);

        $crawler = $this->openPage(self::path() . '/pridat');

        self::assertCount(1, $crawler->filter(sprintf('select[name="%s[product_tp_id]"] option[value="1"]', self::table())));
        self::assertCount(0, $crawler->filter(sprintf('select[name="%s[product_tp_id]"] option[value="%d"]', self::table(), $deleted)));
    }

    #[DataProvider('provideWeights')]
    public function testWeightsMustBeNumbers(string $field, string $value, bool $valid): void
    {
        $this->assertCreateOutcome([$field => $value], $valid);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function provideWeights(): iterable
    {
        foreach (['weight_from', 'weight_to'] as $field) {
            yield $field . ' integer' => [$field, '7', true];
            yield $field . ' decimal' => [$field, '7.5', true];
            yield $field . ' empty (required)' => [$field, '', false];
            yield $field . ' text' => [$field, 'abc', false];
        }
    }
}
