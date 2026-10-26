<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Mailer;

use CeskaKruta\Web\Services\Mailer\PhpMailTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * PLAN §3.8: newsletter keeps PHP mail(), arguments built like Nette's SendmailMailer
 * (To + Subject as separate encoded arguments, the rest of the headers as additional headers, envelope sender via -f).
 */
final class PhpMailTransportTest extends TestCase
{
    /** @var list<array{to: string, subject: string, message: string, headers: string, params: string}> */
    private array $calls = [];

    public function testPassesRecipientsSubjectBodyAndHeadersToMail(): void
    {
        $transport = $this->transport();

        $email = (new Email())
            ->from('info@ceskakruta.cz')
            ->to('first@example.test', 'second@example.test')
            ->subject('Novinky z farmy')
            ->html('<p>Hello newsletter</p>');
        $email->getHeaders()->addTextHeader('X-Custom', 'kept');

        $transport->send($email);

        self::assertCount(1, $this->calls);
        $call = $this->calls[0];

        self::assertSame('first@example.test, second@example.test', $call['to']);
        self::assertSame('Novinky z farmy', $call['subject']);

        self::assertStringContainsString('<p>Hello newsletter</p>', $call['message']);
        self::assertStringNotContainsString('Subject:', $call['message'], 'Body must not contain the header block.');
        self::assertStringNotContainsString('From:', $call['message'], 'Body must not contain the header block.');

        $headerLines = preg_split('/\r?\n/', $call['headers']);
        self::assertIsArray($headerLines);
        self::assertContains('From: info@ceskakruta.cz', $headerLines);
        self::assertContains('MIME-Version: 1.0', $headerLines);
        self::assertContains('X-Custom: kept', $headerLines);
        self::assertMatchesRegularExpression('/^Content-Type: text\/html; charset=utf-8\r?$/m', $call['headers']);

        // To and Subject are passed as separate arguments, mail() would add them a second time
        self::assertDoesNotMatchRegularExpression('/^To:/mi', $call['headers']);
        self::assertDoesNotMatchRegularExpression('/^Subject:/mi', $call['headers']);
        self::assertStringNotContainsString("\n\n", str_replace("\r", '', $call['headers']), 'Headers must not contain an empty line (body separator).');
        self::assertSame(trim($call['headers']), $call['headers']);
    }

    public function testEncodesNonAsciiSubjectAndRecipientName(): void
    {
        $transport = $this->transport();

        $email = (new Email())
            ->from('info@ceskakruta.cz')
            ->to(new Address('jana@example.test', 'Jana Nováková'))
            ->subject('Nová sezóna – Česká Krůta')
            ->html('<p>Žluťoučký kůň</p>');

        $transport->send($email);

        self::assertCount(1, $this->calls);
        $call = $this->calls[0];

        // mail() expects RFC 2047 encoded header values (non-ASCII would be sent raw otherwise)
        self::assertMatchesRegularExpression('/^=\?utf-8\?[QB]\?/i', $call['subject']);
        self::assertSame('Nová sezóna – Česká Krůta', iconv_mime_decode($call['subject'], 0, 'UTF-8'));
        self::assertStringContainsString('<jana@example.test>', $call['to']);
        self::assertStringNotContainsString('Nováková', $call['to']);
        self::assertStringContainsString('Jana Nováková', (string) iconv_mime_decode($call['to'], 0, 'UTF-8'));
    }

    public function testSetsEnvelopeSender(): void
    {
        $transport = $this->transport();

        $email = (new Email())
            ->from('info@ceskakruta.cz')
            ->to('first@example.test')
            ->subject('Envelope')
            ->text('Body');

        // Without an explicit envelope the sender is derived from From
        $transport->send($email);
        // Explicit envelope sender wins
        $transport->send($email, new Envelope(new Address('bounce@ceskakruta.cz'), [new Address('first@example.test')]));

        self::assertCount(2, $this->calls);
        self::assertMatchesRegularExpression("/^-f'?info@ceskakruta\\.cz'?$/", $this->calls[0]['params']);
        self::assertMatchesRegularExpression("/^-f'?bounce@ceskakruta\\.cz'?$/", $this->calls[1]['params']);
    }

    public function testFailureThrowsTransportException(): void
    {
        $transport = new PhpMailTransport(static fn (string $to, string $subject, string $message, string $headers, string $params): bool => false);

        $email = (new Email())
            ->from('info@ceskakruta.cz')
            ->to('first@example.test')
            ->subject('Failing')
            ->text('Body');

        $this->expectException(TransportExceptionInterface::class);

        $transport->send($email);
    }

    public function testToStringIsDsn(): void
    {
        self::assertSame('phpmail://default', (string) new PhpMailTransport());
    }

    private function transport(): PhpMailTransport
    {
        return new PhpMailTransport(function (string $to, string $subject, string $message, string $headers, string $params): bool {
            $this->calls[] = ['to' => $to, 'subject' => $subject, 'message' => $message, 'headers' => $headers, 'params' => $params];

            return true;
        });
    }
}
