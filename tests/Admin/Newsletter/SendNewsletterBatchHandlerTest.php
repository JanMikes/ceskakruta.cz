<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Newsletter;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * PLAN §2.5 newsletter batch (`SendNewsletterBatch`), exercised black-box through the cron endpoint (PLAN §4.3):
 * 1. fill missing: latest actuality (max id), up to 50 distinct active subscribers older than the actuality, not queued yet;
 * 2. send next 10 unsent active non-deleted queue rows; 3. mark them sent.
 */
final class SendNewsletterBatchHandlerTest extends AdminWebTestCase
{
    use NewsletterCronHelpers;

    public function testFillsMissingForLatestActuality(): void
    {
        $this->isolateNewsletterQueue();

        $older = $this->insertActuality('Starší aktualita', '2025-05-01 12:00:00');
        $latest = $this->insertActuality('Nejnovější aktualita', '2025-06-01 12:00:00');

        $this->insertSubscriber('old-1@example.test', '2025-01-01 10:00:00');
        $this->insertSubscriber('old-2@example.test', '2025-05-31 23:59:59');
        $this->insertSubscriber('dup@example.test', '2025-01-01 10:00:00');
        $this->insertSubscriber('dup@example.test', '2025-02-01 10:00:00');
        $this->insertSubscriber('only-older-queued@example.test', '2025-01-01 10:00:00');
        $this->insertSubscriber('already-queued@example.test', '2025-01-01 10:00:00');
        $this->insertSubscriber('same-time@example.test', '2025-06-01 12:00:00'); // not strictly older
        $this->insertSubscriber('newer@example.test', '2025-06-02 10:00:00');
        $this->insertSubscriber('inactive@example.test', '2025-01-01 10:00:00', active: 0);
        $this->insertSubscriber('deleted@example.test', '2025-01-01 10:00:00', deleted: 1);

        // Queued for the older actuality only → still missing for the latest one
        $this->insertQueueRow($older, 'only-older-queued@example.test', sent: 1);
        // Already queued (and sent) for the latest one → not inserted again
        $this->insertQueueRow($latest, 'already-queued@example.test', sent: 1);

        $result = $this->runNewsletterCron();

        self::assertSame(4, $result['queued']);

        $queuedForLatest = $this->connection()->fetchFirstColumn(
            'SELECT email FROM actuality_newsletter WHERE actuality_id = :id ORDER BY email',
            ['id' => $latest],
        );
        self::assertSame([
            'already-queued@example.test',
            'dup@example.test',
            'old-1@example.test',
            'old-2@example.test',
            'only-older-queued@example.test',
        ], $queuedForLatest);

        // The older actuality is never filled
        self::assertSame(1, $this->fetchInt('SELECT COUNT(*) FROM actuality_newsletter WHERE actuality_id = :id', ['id' => $older]));
    }

    public function testFillsAtMostFiftyPerRun(): void
    {
        $this->isolateNewsletterQueue();

        $latest = $this->insertActuality('Velká kampaň');

        for ($i = 1; $i <= 60; $i++) {
            $this->insertSubscriber(sprintf('bulk-%02d@example.test', $i));
        }

        self::assertSame(50, $this->runNewsletterCron()['queued']);
        self::assertSame(50, $this->fetchInt('SELECT COUNT(*) FROM actuality_newsletter WHERE actuality_id = :id', ['id' => $latest]));

        self::assertSame(10, $this->runNewsletterCron()['queued']);
        self::assertSame(60, $this->fetchInt('SELECT COUNT(*) FROM actuality_newsletter WHERE actuality_id = :id', ['id' => $latest]));

        self::assertSame(0, $this->runNewsletterCron()['queued']);
        self::assertSame(60, $this->fetchInt('SELECT COUNT(DISTINCT email) FROM actuality_newsletter WHERE actuality_id = :id', ['id' => $latest]));
    }

    public function testSendsAtMostTenAndMarksSent(): void
    {
        $this->isolateNewsletterQueue();

        $latest = $this->insertActuality('Dávka');

        /** @var array<int, string> $pending id => email */
        $pending = [];
        for ($i = 1; $i <= 12; $i++) {
            $email = sprintf('queue-%02d@example.test', $i);
            $pending[$this->insertQueueRow($latest, $email)] = $email;
        }

        $inactive = $this->insertQueueRow($latest, 'queue-inactive@example.test', active: 0);
        $deleted = $this->insertQueueRow($latest, 'queue-deleted@example.test', deleted: 1);

        // Run 1: exactly 10 sent and marked
        $result = $this->runNewsletterCron();

        self::assertSame(0, $result['queued']);
        self::assertCount(10, $result['sent']);
        self::assertSame([], array_diff($result['sent'], array_keys($pending)), 'Only pending rows may be sent.');
        self::assertEmailCount(10);

        $sentIds = array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $this->connection()->fetchFirstColumn(
            'SELECT id FROM actuality_newsletter WHERE actuality_id = :id AND is_sent_flag = 1 ORDER BY id',
            ['id' => $latest],
        ));
        self::assertSame($this->sorted($result['sent']), $sentIds);

        $recipients = [];
        foreach ($this->sentNewsletterEmails() as $email) {
            self::assertCount(1, $email->getTo(), 'One email per subscriber.');
            $recipients[] = $this->recipientsOf($email)[0];
        }
        $expectedRecipients = array_map(static fn (int $id): string => $pending[$id], $result['sent']);
        sort($recipients);
        sort($expectedRecipients);
        self::assertSame($expectedRecipients, $recipients);

        $row = $this->fetchRow('actuality_newsletter', $result['sent'][0]);
        self::assertNotNull($row['upd_process']);

        // Run 2: the remaining 2
        $result2 = $this->runNewsletterCron();

        self::assertCount(2, $result2['sent']);
        self::assertSame(
            $this->sorted(array_keys($pending)),
            $this->sorted(array_merge($result['sent'], $result2['sent'])),
        );
        self::assertEmailCount(2);

        // Run 3: nothing left
        $result3 = $this->runNewsletterCron();

        self::assertSame([], $result3['sent']);
        self::assertEmailCount(0);

        // Inactive / deleted queue rows are never sent
        self::assertSame(0, $this->fetchInt('SELECT `is_sent_flag` FROM `actuality_newsletter` WHERE id = :id', ['id' => $inactive]));
        self::assertSame(0, $this->fetchInt('SELECT `is_sent_flag` FROM `actuality_newsletter` WHERE id = :id', ['id' => $deleted]));
    }

    public function testSendsQueuedRowsOfOlderActualitiesToo(): void
    {
        $this->isolateNewsletterQueue();

        $older = $this->insertActuality('Rozesílaná dříve', '2025-05-01 12:00:00');
        $this->insertActuality('Nejnovější', '2025-06-01 12:00:00');

        $row = $this->insertQueueRow($older, 'late@example.test');

        $result = $this->runNewsletterCron();

        self::assertSame([$row], $result['sent']);

        $emails = $this->sentNewsletterEmails();
        self::assertCount(1, $emails);
        self::assertSame('Rozesílaná dříve', $emails[0]->getSubject());
    }

    public function testEmailHasSubjectBodyAndUnsubscribeLink(): void
    {
        $this->isolateNewsletterQueue();

        $subscriberEmail = 'jana.novakova@example.test';
        $subscriber = $this->insertSubscriber($subscriberEmail, '2025-01-01 10:00:00');

        $latest = $this->insertActuality(
            'Nová sezóna krůt',
            '2025-06-01 12:00:00',
            '<p>Přijďte <strong>ochutnat</strong> naše krůty</p>',
            '<p>JEN NA WEBU</p>',
        );
        $this->insertQueueRow($latest, $subscriberEmail);

        $result = $this->runNewsletterCron();

        self::assertSame(0, $result['queued'], 'Already queued subscriber must not be queued again.');
        self::assertCount(1, $result['sent']);

        $emails = $this->sentNewsletterEmails();
        self::assertCount(1, $emails);
        $email = $emails[0];

        self::assertSame([$subscriberEmail], $this->recipientsOf($email));
        self::assertSame('Nová sezóna krůt', $email->getSubject());
        // Same sender as the old app (config.parameters.neon mailer.from, PLAN §3.11)
        self::assertSame(['info@ceskakruta.cz'], array_map(static fn ($address): string => $address->getAddress(), $email->getFrom()));

        $html = (string) $email->getHtmlBody();

        self::assertMatchesRegularExpression('~<h1[^>]*>\s*Nová sezóna krůt\s*</h1>~u', $html);
        // actuality.text is rendered as raw HTML, text_full (web text) is not part of the email
        self::assertStringContainsString('<p>Přijďte <strong>ochutnat</strong> naše krůty</p>', $html);
        self::assertStringNotContainsString('JEN NA WEBU', $html);
        self::assertMatchesRegularExpression('~<img[^>]+src="[^"]*logo[^"]*"~', $html);

        // Unsubscribe link: https://ceskakruta.cz/odhlasit-newsletter/{email}/{md5('unsubscribe'.email.'email')}
        $router = self::getContainer()->get(RouterInterface::class);
        $path = $router->generate('unsubscribe_newsletter', [
            'email' => $subscriberEmail,
            'hash' => md5('unsubscribe' . $subscriberEmail . 'email'),
        ]);
        self::assertSame('/odhlasit-newsletter/jana.novakova@example.test/' . md5('unsubscribe' . $subscriberEmail . 'email'), $path);

        $link = 'https://ceskakruta.cz' . $path;
        self::assertStringContainsString('href="' . $link . '"', $html);

        // The link really unsubscribes (existing frontend route)
        $this->client->request('GET', $path);
        self::assertResponseRedirects();
        self::assertSame(1, $this->fetchInt('SELECT `del_flag` FROM `newsletter` WHERE id = :id', ['id' => $subscriber]));
    }
}
