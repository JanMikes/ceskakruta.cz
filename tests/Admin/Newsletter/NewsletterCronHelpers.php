<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Newsletter;

use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

/**
 * Arrange/act helpers for the newsletter cron (PLAN §2.5, §3.8), used by AdminWebTestCase subclasses.
 */
trait NewsletterCronHelpers
{
    private const CRON_TOKEN = 'test-cron-token';

    /**
     * Isolates the queue from rows of other fixtures: every existing queue row is marked sent
     * and every existing subscriber is soft deleted (so "fill missing" only sees subscribers added by the test).
     */
    private function isolateNewsletterQueue(): void
    {
        $this->connection()->executeStatement('UPDATE actuality_newsletter SET is_sent_flag = 1');
        $this->connection()->executeStatement('UPDATE newsletter SET del_flag = 1');
    }

    /**
     * Inserts an actuality, it becomes the latest one (highest id) for the cron.
     */
    private function insertActuality(string $name, string $insDt = '2025-06-01 12:00:00', string $text = '<p>Text emailu</p>', string $textFull = '<p>Text webu</p>'): int
    {
        return $this->insert('actuality', [
            'date' => substr($insDt, 0, 10),
            'name' => $name,
            'text' => $text,
            'text_full' => $textFull,
            'ins_dt' => $insDt,
        ]);
    }

    private function insertSubscriber(string $email, string $insDt = '2025-01-01 10:00:00', int $active = 1, int $deleted = 0): int
    {
        return $this->insert('newsletter', [
            'email' => $email,
            'ins_dt' => $insDt,
            'active_flag' => $active,
            'del_flag' => $deleted,
        ]);
    }

    private function insertQueueRow(int $actualityId, string $email, int $sent = 0, int $active = 1, int $deleted = 0): int
    {
        return $this->insert('actuality_newsletter', [
            'actuality_id' => $actualityId,
            'email' => $email,
            'is_sent_flag' => $sent,
            'active_flag' => $active,
            'del_flag' => $deleted,
        ]);
    }

    /**
     * Calls the cron endpoint and returns the decoded JSON {"queued": int, "sent": list<int>}.
     *
     * @return array{queued: int, sent: list<int>}
     */
    private function runNewsletterCron(): array
    {
        $this->client->request('GET', '/cron/send-email?token=' . self::CRON_TOKEN);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($data);
        self::assertSame(['queued', 'sent'], array_keys($data));
        self::assertIsInt($data['queued']);
        self::assertIsList($data['sent']);

        $sent = [];
        foreach ($data['sent'] as $id) {
            self::assertIsInt($id);
            $sent[] = $id;
        }

        return ['queued' => $data['queued'], 'sent' => $sent];
    }

    /**
     * Emails actually handed to a transport during the last request (queued = before the messenger handler, skipped).
     *
     * @return list<Email>
     */
    private function sentNewsletterEmails(): array
    {
        $emails = [];

        foreach (self::getMailerEvents() as $event) {
            if ($event->isQueued()) {
                continue;
            }

            $message = $event->getMessage();
            self::assertInstanceOf(Email::class, $message);
            $emails[] = $message;
        }

        return $emails;
    }

    /**
     * Events dispatched by Mailer::send() before routing to a transport, the only place where X-Transport is still present
     * (Transports removes the header before the chosen transport sends).
     *
     * @return list<MessageEvent>
     */
    private function queuedMailerEvents(): array
    {
        return array_values(array_filter(self::getMailerEvents(), static fn (MessageEvent $event): bool => $event->isQueued()));
    }

    /**
     * @return list<string>
     */
    private function recipientsOf(Email $email): array
    {
        return array_values(array_map(static fn ($address): string => $address->getAddress(), $email->getTo()));
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function sorted(array $ids): array
    {
        sort($ids);

        return $ids;
    }
}
