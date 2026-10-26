<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Actuality;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PLAN §2.4 + §4.6 forms: edit title includes the actuality name, "Uložit" → list, "Uložit a pokračovat v editaci" → form,
 * unknown id → redirect to list. Editing never enqueues the newsletter.
 */
final class EditActualityTest extends AdminWebTestCase
{
    use SubmitsAdminForm;

    public function testFormIsPrefilledAndTitleContainsName(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/aktuality/1000');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Jarní akce');

        $values = $this->formByButton($crawler, 'Uložit')->getValues();
        self::assertSame('2024-03-01', $values['actuality[date]']);
        self::assertSame('Jarní akce', $values['actuality[name]']);
        self::assertSame('<p>Jarní text do emailu</p>', $values['actuality[text]']);
        self::assertSame('<p>Jarní text na web</p>', $values['actuality[text_full]']);
    }

    public function testEdits(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/aktuality/1000');
        self::assertResponseIsSuccessful();

        $this->client->submit($this->formByButton($crawler, 'Uložit'), [
            'actuality[date]' => '2024-03-02',
            'actuality[name]' => 'Jarní akce – prodlouženo',
            'actuality[text]' => '<p>Nový email</p>',
            'actuality[text_full]' => '<p>Nový web</p>',
        ]);

        $this->assertRedirectsTo('/admin/aktuality', 1000);

        $row = $this->fetchRow('actuality', 1000);
        self::assertSame('2024-03-02', $row['date']);
        self::assertSame('Jarní akce – prodlouženo', $row['name']);
        self::assertSame('<p>Nový email</p>', $row['text']);
        self::assertSame('<p>Nový web</p>', $row['text_full']);
        self::assertSame('admin', $row['upd_process']);
        self::assertSame((string) self::ADMIN_ID, $row['upd_user_id']);
        // Insert audit untouched
        self::assertSame('2024-03-01 08:00:00', $row['ins_dt']);

        // Edit does not enqueue the newsletter
        self::assertSame(0, $this->fetchInt('SELECT COUNT(*) FROM actuality_newsletter WHERE actuality_id = 1000'));

        $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
    }

    public function testSaveAndContinueStaysOnForm(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/aktuality/1000');
        self::assertResponseIsSuccessful();

        $this->client->submit($this->formByButton($crawler, 'Uložit a pokračovat v editaci'), [
            'actuality[name]' => 'Jarní akce 2',
        ]);

        $this->assertRedirectsTo('/admin/aktuality/1000');
        self::assertSame('Jarní akce 2', $this->fetchRow('actuality', 1000)['name']);

        $crawler = $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
        self::assertSame('Jarní akce 2', $this->formByButton($crawler, 'Uložit')->getValues()['actuality[name]']);
    }

    public function testInvalidEditKeepsRow(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/aktuality/1000');
        self::assertResponseIsSuccessful();

        $this->client->submit($this->formByButton($crawler, 'Uložit'), [
            'actuality[name]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::assertSame('Jarní akce', $this->fetchRow('actuality', 1000)['name']);
    }

    #[DataProvider('provideUnknownIds')]
    public function testUnknownIdRedirectsToList(int $id): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/aktuality/' . $id);

        self::assertResponseStatusCodeSame(302);
        $this->assertRedirectsTo('/admin/aktuality');
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideUnknownIds(): iterable
    {
        yield 'not existing' => [999999];
        yield 'soft deleted' => [1002];
    }
}
