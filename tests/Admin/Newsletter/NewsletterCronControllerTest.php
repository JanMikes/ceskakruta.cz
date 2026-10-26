<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Newsletter;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PLAN §3.8: GET /cron/send-email?token=… (same path as the old app), JSON {"queued": n, "sent": [ids]},
 * token = env CRON_TOKEN (test: "test-cron-token"), missing/wrong → 403, failure → 5xx (so curl -f alerts),
 * newsletter emails use the `newsletter` mailer transport (X-Transport header).
 */
final class NewsletterCronControllerTest extends AdminWebTestCase
{
    use NewsletterCronHelpers;

    private const NEWSLETTER_DSN = 'MAILER_NEWSLETTER_DSN';

    /** @var array{env: mixed, server: mixed}|null */
    private null|array $originalDsn = null;

    protected function tearDown(): void
    {
        if ($this->originalDsn !== null) {
            $this->restoreEnv(self::NEWSLETTER_DSN, $this->originalDsn);
            $this->originalDsn = null;
        }

        parent::tearDown();
    }

    public function testTriggerReturnsJsonAndSends(): void
    {
        $this->isolateNewsletterQueue();

        $latest = $this->insertActuality('Cron aktualita');
        $this->insertSubscriber('cron@example.test', '2025-01-01 10:00:00');

        // Anonymous request (curl from lily / VPS), no admin login
        $result = $this->runNewsletterCron();

        $queueRowId = $this->fetchInt(
            'SELECT id FROM actuality_newsletter WHERE actuality_id = :id AND email = :email',
            ['id' => $latest, 'email' => 'cron@example.test'],
        );

        self::assertSame(['queued' => 1, 'sent' => [$queueRowId]], $result);
        self::assertSame(1, $this->fetchInt('SELECT `is_sent_flag` FROM `actuality_newsletter` WHERE id = :id', ['id' => $queueRowId]));
        self::assertEmailCount(1);
    }

    #[DataProvider('provideInvalidTokens')]
    public function testRejectsMissingOrWrongToken(string $url): void
    {
        $this->isolateNewsletterQueue();

        $latest = $this->insertActuality('Neodeslat');
        $this->insertSubscriber('not-yet@example.test');
        $row = $this->insertQueueRow($latest, 'pending@example.test');
        $queueCount = $this->fetchInt('SELECT COUNT(*) FROM actuality_newsletter');

        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, $this->fetchInt('SELECT `is_sent_flag` FROM `actuality_newsletter` WHERE id = :id', ['id' => $row]));
        self::assertSame($queueCount, $this->fetchInt('SELECT COUNT(*) FROM actuality_newsletter'), 'Nothing may be queued.');
        self::assertEmailCount(0);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidTokens(): iterable
    {
        yield 'missing token' => ['/cron/send-email'];
        yield 'empty token' => ['/cron/send-email?token='];
        yield 'wrong token' => ['/cron/send-email?token=wrong-token'];
        yield 'token prefix' => ['/cron/send-email?token=test-cron'];
    }

    public function testFailureReturns5xx(): void
    {
        // Newsletter transport that can not deliver (nothing listens on port 1) → sending throws
        $this->originalDsn = $this->overrideEnv(self::NEWSLETTER_DSN, 'smtp://127.0.0.1:1');
        self::ensureKernelShutdown();
        $this->client = self::createClient();

        $this->isolateNewsletterQueue();

        $latest = $this->insertActuality('Selhání');
        $row = $this->insertQueueRow($latest, 'fail@example.test');

        $this->client->request('GET', '/cron/send-email?token=' . self::CRON_TOKEN);

        self::assertGreaterThanOrEqual(500, $this->client->getResponse()->getStatusCode());
        self::assertLessThan(600, $this->client->getResponse()->getStatusCode());

        // Not delivered → must stay in the queue for the next run
        self::assertSame(0, $this->fetchInt('SELECT `is_sent_flag` FROM `actuality_newsletter` WHERE id = :id', ['id' => $row]));
    }

    public function testUsesNewsletterTransport(): void
    {
        $this->isolateNewsletterQueue();

        $latest = $this->insertActuality('Transport');
        $this->insertQueueRow($latest, 'transport-1@example.test');
        $this->insertQueueRow($latest, 'transport-2@example.test');

        $result = $this->runNewsletterCron();
        self::assertCount(2, $result['sent']);

        $events = $this->queuedMailerEvents();
        self::assertCount(2, $events);

        foreach ($events as $event) {
            self::assertEmailHeaderSame($event->getMessage(), 'X-Transport', 'newsletter');
        }
    }

    /**
     * @return array{env: mixed, server: mixed}
     */
    private function overrideEnv(string $name, string $value): array
    {
        $original = ['env' => $_ENV[$name] ?? null, 'server' => $_SERVER[$name] ?? null];

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;

        return $original;
    }

    /**
     * @param array{env: mixed, server: mixed} $original
     */
    private function restoreEnv(string $name, array $original): void
    {
        if ($original['env'] === null) {
            unset($_ENV[$name]);
        } else {
            $_ENV[$name] = $original['env'];
        }

        if ($original['server'] === null) {
            unset($_SERVER[$name]);
        } else {
            $_SERVER[$name] = $original['server'];
        }
    }
}
