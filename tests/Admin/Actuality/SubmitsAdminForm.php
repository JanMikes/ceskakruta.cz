<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Actuality;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * Selects a submit button by its exact label ("Uložit" vs "Uložit a pokračovat v editaci", PLAN §4.6),
 * DomCrawler's selectButton() matches words, so "Uložit" would match both buttons.
 */
trait SubmitsAdminForm
{
    private function formByButton(Crawler $crawler, string $label): Form
    {
        $buttons = $crawler->filterXPath(sprintf(
            '//button[normalize-space(.)="%1$s"] | //input[(@type="submit" or @type="button") and @value="%1$s"]',
            $label,
        ));

        self::assertCount(1, $buttons, sprintf('Exactly one button "%s" expected.', $label));

        return $buttons->form();
    }
}
