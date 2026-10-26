<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\User;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Form;

/**
 * Users contract (PLAN §2.16, §4.6): rows `id="item-{id}"`; list is a table with cells
 * ID, Registrace, E-mail, Jméno, Počet objednávek, "N zakázaných", Velkoobchod, Upravit.
 */
abstract class UserTestCase extends AdminWebTestCase
{
    // tests/database/fixtures/40-places-users.sql
    protected const USER_WITH_ORDERS = 4001;
    protected const USER_ADMIN = 4002;
    protected const USER_PLAIN = 4003;
    protected const USER_WHOLESALE = 4004;
    protected const USER_EDIT = 4005;

    protected const USERS_URL = '/admin/uzivatele';

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

    protected static function single(Crawler $nodes, string $what): Crawler
    {
        self::assertCount(1, $nodes, sprintf('Expected exactly one %s.', $what));

        return $nodes;
    }

    protected function userRow(Crawler $crawler, int $id): Crawler
    {
        $row = $crawler->filter(sprintf('[id="item-%d"]', $id));
        self::assertCount(1, $row, sprintf('User row #%d not found.', $id));

        return $row;
    }

    /**
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

    protected function locationPath(): string
    {
        $location = (string) $this->client->getResponse()->headers->get('Location');
        $location = (string) preg_replace('~^https?://[^/]+~', '', $location);

        return explode('#', $location, 2)[0];
    }

    /**
     * Submits a form with raw PHP values (lets tests post exactly what they want, e.g. omit checkbox lists).
     *
     * @param array<mixed> $values
     */
    protected function submitValues(Form $form, array $values): Crawler
    {
        return $this->client->request($form->getMethod(), $form->getUri(), $values);
    }

    /**
     * @return list<int>
     */
    protected function bannedProductIds(int $userId): array
    {
        return array_map(static fn (mixed $id): int => self::toInt($id), $this->connection()->fetchFirstColumn(
            'SELECT product_id FROM user_banned_product WHERE user_id = :id ORDER BY product_id',
            ['id' => $userId],
        ));
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
