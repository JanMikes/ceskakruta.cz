<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Product;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * Submits admin forms the way a browser does (PLAN §4.6: buttons "Uložit" / "Uložit a pokračovat v editaci"),
 * but as a raw POST built from the rendered form, so tests can also post values the form does not render
 * (forged fields) or omit fields (unticked checkboxes) without DomCrawler validating them.
 */
trait SubmitsAdminForms
{
    protected function adminForm(Crawler $crawler, string $button = 'Uložit'): Form
    {
        $buttons = $crawler->filterXPath(sprintf(
            '//button[normalize-space(.)="%1$s"] | //input[(@type="submit" or @type="button") and @value="%1$s"]',
            $button,
        ));
        self::assertGreaterThan(0, $buttons->count(), sprintf('Button "%s" not found.', $button));

        return $buttons->first()->form();
    }

    /**
     * @param array<string, string> $fields flat field names, e.g. `product[name]` => 'x'
     * @param list<string> $omit flat field names to leave out of the request (e.g. unticked checkboxes)
     * @param array<string, mixed> $files nested files, e.g. ['recipe' => ['image' => UploadedFile]]
     */
    protected function submitAdminForm(Crawler $crawler, array $fields = [], array $omit = [], array $files = [], string $button = 'Uložit'): Crawler
    {
        $form = $this->adminForm($crawler, $button);

        $values = $form->getValues();
        foreach ($omit as $name) {
            unset($values[$name]);
        }
        foreach ($fields as $name => $value) {
            $values[$name] = $value;
        }

        parse_str(http_build_query($values), $parameters);

        return $this->client->request('POST', $form->getUri(), $parameters, $files);
    }

    /**
     * Value of a rendered form field (input value, textarea content, selected option).
     */
    protected function fieldValue(Crawler $crawler, string $name): string
    {
        $field = $crawler->filter(sprintf('[name="%s"]', $name));
        self::assertCount(1, $field, sprintf('Field "%s" not found.', $name));

        return match ($field->nodeName()) {
            'textarea' => $field->text('', false),
            'select' => $field->filter('option[selected]')->count() > 0 ? (string) $field->filter('option[selected]')->attr('value') : '',
            default => (string) $field->attr('value'),
        };
    }

    protected function assertFieldNotRendered(Crawler $crawler, string $name): void
    {
        self::assertCount(0, $crawler->filter(sprintf('[name="%s"]', $name)), sprintf('Field "%s" must not be rendered.', $name));
    }

    protected function asInt(mixed $value): int
    {
        self::assertIsNumeric($value);

        return (int) $value;
    }

    protected function asFloat(mixed $value): float
    {
        self::assertIsNumeric($value);

        return (float) $value;
    }
}
