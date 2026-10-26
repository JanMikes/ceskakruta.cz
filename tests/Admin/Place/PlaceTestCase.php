<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Place;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Form;

/**
 * Places page contract (PLAN §2.15, §4.6): places rows `id="item-{id}"`, closed-day rows `id="closed-day-{id}"`,
 * places list is a table: Název, Rozvoz, Pondělí … Neděle, then action cells.
 */
abstract class PlaceTestCase extends AdminWebTestCase
{
    // tests/database/fixtures/40-places-users.sql
    protected const PLACE_DAYS = 4001;
    protected const PLACE_HIDDEN = 4002;
    protected const PLACE_COOL = 4003;
    protected const PLACE_DELETED = 4004;
    protected const PLACE_INACTIVE = 4005;

    protected const PLACES_URL = '/admin/prodejny';
    protected const PLACE_ADD_URL = '/admin/prodejny/pridat';
    protected const CLOSED_DAY_ADD_URL = '/admin/prodejny/zavreno/pridat';

    /**
     * Exactly the button with this label (selectButton() would also match "Uložit a pokračovat v editaci").
     */
    protected function formByButton(Crawler $crawler, string $label = 'Uložit'): Form
    {
        self::assertResponseIsSuccessful();

        $buttons = $crawler->filterXPath(sprintf(
            '//button[normalize-space(.)="%1$s"] | //input[(@type="submit" or @type="button") and @value="%1$s"]',
            $label,
        ));
        self::assertGreaterThan(0, $buttons->count(), sprintf('Button "%s" not found.', $label));

        return $buttons->first()->form();
    }

    /**
     * @return list<int>
     */
    protected function closedDayIds(Crawler $crawler): array
    {
        return array_values(array_map(
            static fn (string $id): int => (int) substr($id, strlen('closed-day-')),
            $crawler->filter('[id^="closed-day-"]')->each(static fn (Crawler $node): string => (string) $node->attr('id')),
        ));
    }

    protected function closedDayRow(Crawler $crawler, int $id): Crawler
    {
        $row = $crawler->filter(sprintf('[id="closed-day-%d"]', $id));
        self::assertCount(1, $row, sprintf('Closed day row #%d not found.', $id));

        return $row;
    }

    protected function placeRow(Crawler $crawler, int $id): Crawler
    {
        $row = $crawler->filter(sprintf('[id="item-%d"]', $id));
        self::assertCount(1, $row, sprintf('Place row #%d not found.', $id));

        return $row;
    }

    /**
     * Normalized text of every cell of a places row.
     *
     * @return list<string>
     */
    protected function cellTexts(Crawler $row): array
    {
        return array_values($row->filter('td')->each(static fn (Crawler $cell): string => self::normalize($cell->text())));
    }

    protected static function normalize(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    protected function countRows(string $table): int
    {
        return $this->fetchInt(sprintf('SELECT COUNT(*) FROM `%s`', $table));
    }

    /**
     * Values of a select in DOM order (empty prompt option excluded), keyed by value.
     *
     * @return array<int, string>
     */
    protected function selectOptions(Crawler $crawler, string $name): array
    {
        $select = $crawler->filter(sprintf('select[name="%s"]', $name));
        self::assertCount(1, $select, sprintf('Select "%s" not found.', $name));

        $options = [];
        foreach ($select->filter('option') as $option) {
            assert($option instanceof \DOMElement);
            $value = $option->getAttribute('value');
            if ($value === '') {
                continue;
            }
            $options[(int) $value] = self::normalize($option->textContent);
        }

        return $options;
    }

    /**
     * Fills the whole place form: all days disabled unless given in $days ([day => [daysBefore, time]]).
     *
     * @param array<string, string|bool> $values field => value (without the `place[...]` wrapper)
     * @param array<int, array{string, string}> $days
     */
    protected function fillPlaceForm(Form $form, array $values, array $days = []): void
    {
        for ($day = 1; $day <= 7; $day++) {
            $enabled = isset($days[$day]);
            $this->setField($form, sprintf('place[enable_day_%d]', $day), $enabled);
            $this->setField($form, sprintf('place[allow_order_day_%d_days_before]', $day), $enabled ? $days[$day][0] : '');
            $this->setField($form, sprintf('place[allow_order_day_%d_time]', $day), $enabled ? $days[$day][1] : '23:59');
        }

        foreach ($values as $field => $value) {
            $this->setField($form, sprintf('place[%s]', $field), $value);
        }
    }

    protected function setField(Form $form, string $name, string|bool $value): void
    {
        $field = $form->get($name);
        self::assertInstanceOf(FormField::class, $field, sprintf('Form field "%s" not found.', $name));

        if (is_bool($value)) {
            self::assertInstanceOf(ChoiceFormField::class, $field, sprintf('Form field "%s" is not a checkbox.', $name));
            $value ? $field->tick() : $field->untick();

            return;
        }

        $field->setValue($value);
    }

    protected function fieldValue(Form $form, string $name): mixed
    {
        $field = $form->get($name);
        self::assertInstanceOf(FormField::class, $field, sprintf('Form field "%s" not found.', $name));

        return $field->getValue();
    }

    protected function isChecked(Form $form, string $name): bool
    {
        $field = $form->get($name);
        self::assertInstanceOf(ChoiceFormField::class, $field, sprintf('Checkbox "%s" not found.', $name));

        return $field->hasValue();
    }

    protected static function toInt(mixed $value): int
    {
        self::assertIsNumeric($value);

        return (int) $value;
    }
}
