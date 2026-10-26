<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Actuality;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PLAN §2.4: create form date (required), name (required, max 50), text, text_full;
 * on create every active & non-deleted subscriber is enqueued into actuality_newsletter (INSERT IGNORE on email + actuality_id).
 */
final class CreateActualityTest extends AdminWebTestCase
{
    use SubmitsAdminForm;

    public function testCreates(): void
    {
        $this->loginAsAdmin();
        $before = $this->lastInsertedId('actuality');

        $crawler = $this->client->request('GET', '/admin/aktuality/pridat');
        self::assertResponseIsSuccessful();

        // Create has only "Uložit", no "save and continue"
        self::assertCount(0, $crawler->filterXPath('//button[normalize-space(.)="Uložit a pokračovat v editaci"]'));

        $this->client->submit($this->formByButton($crawler, 'Uložit'), [
            'actuality[date]' => '2025-04-10',
            'actuality[name]' => 'Velikonoční krůty',
            'actuality[text]' => '<p>Text <strong>do emailu</strong></p>',
            'actuality[text_full]' => '<p>Text na web</p>',
        ]);

        $id = $this->lastInsertedId('actuality');
        self::assertGreaterThan($before, $id);
        $this->assertRedirectsTo('/admin/aktuality', $id);

        $row = $this->fetchRow('actuality', $id);
        self::assertSame('2025-04-10', $row['date']);
        self::assertSame('Velikonoční krůty', $row['name']);
        self::assertSame('<p>Text <strong>do emailu</strong></p>', $row['text']);
        self::assertSame('<p>Text na web</p>', $row['text_full']);
        self::assertEquals(1, $row['active_flag']);
        self::assertEquals(0, $row['del_flag']);
        self::assertSame('admin', $row['ins_process']);
        self::assertSame((string) self::ADMIN_ID, $row['ins_user_id']);
        self::assertIsString($row['ins_dt']);
        self::assertStringStartsWith(date('Y-m-d'), $row['ins_dt']);

        $crawler = $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
        self::assertContains($id, $this->listedIds($crawler));
    }

    /**
     * @param array<string, string> $values
     */
    #[DataProvider('provideInvalidValues')]
    public function testValidation(array $values): void
    {
        $this->loginAsAdmin();
        $actualities = $this->fetchInt('SELECT COUNT(*) FROM actuality');
        $queue = $this->fetchInt('SELECT COUNT(*) FROM actuality_newsletter');

        $crawler = $this->client->request('GET', '/admin/aktuality/pridat');
        self::assertResponseIsSuccessful();

        $this->client->submit($this->formByButton($crawler, 'Uložit'), $values + [
            'actuality[date]' => '2025-04-10',
            'actuality[name]' => 'Platný nadpis',
            'actuality[text]' => '<p>Email</p>',
            'actuality[text_full]' => '<p>Web</p>',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::assertSame($actualities, $this->fetchInt('SELECT COUNT(*) FROM actuality'));
        self::assertSame($queue, $this->fetchInt('SELECT COUNT(*) FROM actuality_newsletter'));
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'missing name' => [['actuality[name]' => '']];
        yield 'name longer than 50' => [['actuality[name]' => str_repeat('a', 51)]];
        yield 'missing date' => [['actuality[date]' => '']];
        yield 'invalid date' => [['actuality[date]' => 'not-a-date']];
    }

    public function testNameOfExactly50CharactersIsValid(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/aktuality/pridat');
        self::assertResponseIsSuccessful();

        $name = str_repeat('ř', 50);
        $this->client->submit($this->formByButton($crawler, 'Uložit'), [
            'actuality[date]' => '2025-04-10',
            'actuality[name]' => $name,
        ]);

        $id = $this->lastInsertedId('actuality');
        $this->assertRedirectsTo('/admin/aktuality', $id);
        self::assertSame($name, $this->fetchRow('actuality', $id)['name']);
    }

    public function testEnqueuesNewsletterForActiveSubscribers(): void
    {
        $this->loginAsAdmin();

        $id = $this->createActuality();

        $expected = $this->connection()->fetchFirstColumn('SELECT DISTINCT email FROM newsletter WHERE active_flag = 1 AND del_flag = 0 ORDER BY email');
        self::assertContains('adam@example.test', $expected);
        self::assertContains('zuzana@example.test', $expected);

        $queued = $this->connection()->fetchAllAssociative(
            'SELECT email, is_sent_flag, active_flag, del_flag FROM actuality_newsletter WHERE actuality_id = :id ORDER BY email',
            ['id' => $id],
        );

        self::assertSame($expected, array_column($queued, 'email'));

        foreach ($queued as $row) {
            self::assertEquals(['email' => $row['email'], 'is_sent_flag' => 0, 'active_flag' => 1, 'del_flag' => 0], $row);
        }

        // Creating only enqueues, sending is the cron's job
        self::assertEmailCount(0);
    }

    public function testDuplicateSubscriberEmailIsQueuedOnce(): void
    {
        $this->loginAsAdmin();

        // Same address subscribed twice (possible in the legacy table, unique index is only on actuality_newsletter)
        $this->insert('newsletter', ['email' => 'adam@example.test', 'active_flag' => 1, 'del_flag' => 0]);

        $id = $this->createActuality();

        self::assertSame(1, $this->fetchInt(
            'SELECT COUNT(*) FROM actuality_newsletter WHERE actuality_id = :id AND email = :email',
            ['id' => $id, 'email' => 'adam@example.test'],
        ));
    }

    public function testIgnoresDeletedAndInactiveSubscribers(): void
    {
        $this->loginAsAdmin();

        $id = $this->createActuality();

        $queued = $this->connection()->fetchFirstColumn('SELECT email FROM actuality_newsletter WHERE actuality_id = :id', ['id' => $id]);

        self::assertContains('marek@example.test', $queued);
        self::assertNotContains('inactive@example.test', $queued);
        self::assertNotContains('deleted@example.test', $queued);
    }

    private function createActuality(): int
    {
        $crawler = $this->client->request('GET', '/admin/aktuality/pridat');
        self::assertResponseIsSuccessful();

        $this->client->submit($this->formByButton($crawler, 'Uložit'), [
            'actuality[date]' => '2025-05-01',
            'actuality[name]' => 'Nová aktualita',
            'actuality[text]' => '<p>Email</p>',
            'actuality[text_full]' => '<p>Web</p>',
        ]);

        $id = $this->lastInsertedId('actuality');
        $this->assertRedirectsTo('/admin/aktuality', $id);

        return $id;
    }
}
