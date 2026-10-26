<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Form;

/**
 * Form helpers shared by the Calendar / Sortiment / Content / Vendor admin tests (PLAN §4.6 "Forms").
 * Use only in subclasses of AdminWebTestCase.
 */
trait AdminFormHelpers
{
    protected const SAVE = 'Uložit';
    protected const SAVE_AND_CONTINUE = 'Uložit a pokračovat v editaci';
    protected const SAVED_FLASH = 'Záznam byl úspěšně uložen!';

    /**
     * GETs an admin page and asserts it rendered (makes "route missing" failures obvious).
     */
    protected function openPage(string $url): Crawler
    {
        $crawler = $this->client->request('GET', $url);
        self::assertResponseIsSuccessful(sprintf('GET %s did not render.', $url));

        return $crawler;
    }

    /**
     * Form of the submit button with exactly this label ("Uložit" vs "Uložit a pokračovat v editaci").
     */
    protected function formByButton(Crawler $crawler, string $label = self::SAVE): Form
    {
        $buttons = $crawler->filter('button[type="submit"], button:not([type]), input[type="submit"]')->reduce(
            static fn (Crawler $node): bool => trim($node->text('')) === $label || $node->attr('value') === $label,
        );
        self::assertCount(1, $buttons, sprintf('Expected exactly one submit button "%s".', $label));

        return $buttons->form();
    }

    /**
     * Submits the form of the button, values keyed by column name, prefixed with the form name: `name[column]`.
     *
     * @param array<string, string|bool> $values
     */
    protected function submitAdminForm(Crawler $crawler, string $formName, array $values, string $button = self::SAVE): Crawler
    {
        $form = $this->formByButton($crawler, $button);

        foreach ($values as $field => $value) {
            $fieldName = sprintf('%s[%s]', $formName, $field);
            self::assertTrue($form->has($fieldName), sprintf('Form field "%s" not found.', $fieldName));
            $formField = $form->get($fieldName);

            if (is_bool($value)) {
                self::assertInstanceOf(ChoiceFormField::class, $formField, sprintf('Field "%s" must be a checkbox.', $fieldName));
                $value ? $formField->tick() : $formField->untick();
            } else {
                self::assertInstanceOf(FormField::class, $formField);
                $formField->setValue($value);
            }
        }

        return $this->client->submit($form);
    }

    /**
     * Current value of a single form field (checkbox: null when unchecked).
     */
    protected function fieldValue(Form $form, string $name): null|string
    {
        self::assertTrue($form->has($name), sprintf('Form field "%s" not found.', $name));
        $field = $form->get($name);
        self::assertInstanceOf(FormField::class, $field);
        $value = $field->getValue();
        self::assertFalse(is_array($value));

        return $value;
    }

    protected static function intValue(mixed $value): int
    {
        self::assertIsNumeric($value);

        return (int) $value;
    }

    protected static function floatValue(mixed $value): float
    {
        self::assertIsNumeric($value);

        return (float) $value;
    }

    /**
     * First `<td>` text of the row.
     */
    protected function firstCell(Crawler $crawler, int $id): string
    {
        $cells = $this->rowCells($crawler, $id);
        self::assertNotEmpty($cells, sprintf('Row #item-%d has no cells.', $id));

        return $cells[0];
    }

    protected function assertFormRejected(): void
    {
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
    }

    protected function countRows(string $table): int
    {
        return $this->fetchInt(sprintf('SELECT COUNT(*) FROM `%s`', $table));
    }

    protected function rowText(Crawler $crawler, int $id): string
    {
        $row = $crawler->filter('#item-' . $id);
        self::assertCount(1, $row, sprintf('Row #item-%d not found.', $id));

        return (string) preg_replace('/\s+/u', ' ', $row->text());
    }

    /**
     * Texts of the row's `<td>` cells (whitespace normalised), in DOM order.
     *
     * @return list<string>
     */
    protected function rowCells(Crawler $crawler, int $id): array
    {
        $row = $crawler->filter('#item-' . $id);
        self::assertCount(1, $row, sprintf('Row #item-%d not found.', $id));

        return array_values($row->filter('td')->each(
            static fn (Crawler $cell): string => trim((string) preg_replace('/\s+/u', ' ', $cell->text(''))),
        ));
    }

    /**
     * @param array<string, mixed> $row
     */
    protected static function assertInsertAudit(array $row): void
    {
        self::assertNotEmpty($row['ins_dt']);
        self::assertSame('admin', $row['ins_process']);
        self::assertSame((string) self::ADMIN_ID, $row['ins_user_id']);
        self::assertSame(1, self::intValue($row['active_flag']));
        self::assertSame(0, self::intValue($row['del_flag']));
    }

    /**
     * @param array<string, mixed> $row
     */
    protected static function assertUpdateAudit(array $row): void
    {
        self::assertSame('admin', $row['upd_process']);
        self::assertSame((string) self::ADMIN_ID, $row['upd_user_id']);
    }
}
