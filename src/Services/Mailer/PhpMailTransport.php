<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Mailer;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Sends messages through PHP's mail() (the hosting's server-configured delivery), like Nette's SendmailMailer did.
 * DSN: phpmail://default (see PhpMailTransportFactory, PLAN §3.8).
 *
 * mail() receives the encoded "To" header value, the encoded "Subject" header value, the encoded body and the remaining
 * headers (without To/Subject); the envelope sender is passed as additional parameter "-f<sender>".
 */
#[Exclude]
final class PhpMailTransport extends AbstractTransport
{
    /**
     * Same signature as mail(): (to, subject, message, additional_headers, additional_params) → bool.
     *
     * @var \Closure(string, string, string, string, string): bool
     */
    private \Closure $mail;

    /**
     * @param null|\Closure(string, string, string, string, string): bool $mail injectable for tests, defaults to mail()
     */
    public function __construct(
        null|\Closure $mail = null,
        null|EventDispatcherInterface $dispatcher = null,
        null|LoggerInterface $logger = null,
    ) {
        parent::__construct($dispatcher, $logger);

        $this->mail = $mail ?? static fn (string $to, string $subject, string $message, string $headers, string $params): bool => mail($to, $subject, $message, $headers, $params);
    }

    public function __toString(): string
    {
        return 'phpmail://default';
    }

    protected function doSend(SentMessage $message): void
    {
        $parts = explode("\r\n\r\n", $message->toString(), 2);
        $body = $parts[1] ?? '';

        $to = null;
        $subject = '';
        $headers = [];

        foreach (self::splitHeaderFields($parts[0]) as $field) {
            [$name, $value] = explode(':', $field, 2) + [1 => ''];
            $value = ltrim($value, ' ');

            match (strtolower($name)) {
                'to' => $to = $value,
                'subject' => $subject = $value,
                default => $headers[] = $field,
            };
        }

        if ($to === null) {
            $to = implode(', ', array_map(static fn ($address): string => $address->getEncodedAddress(), $message->getEnvelope()->getRecipients()));
        }

        $error = null;
        set_error_handler(static function (int $errno, string $errstr) use (&$error): bool {
            $error = $errstr;

            return true;
        });

        try {
            $result = ($this->mail)(
                self::eol($to),
                self::eol($subject),
                self::eol($body),
                self::eol(implode("\r\n", $headers)),
                self::envelopeSenderParameter($message->getEnvelope()->getSender()->getAddress()),
            );
        } finally {
            restore_error_handler();
        }

        if ($result !== true) {
            throw new TransportException('Unable to send email' . ($error !== null ? ': ' . $error : '.'));
        }
    }

    /**
     * Header fields of the encoded header block, folded continuation lines stay with their field.
     *
     * @return list<string>
     */
    private static function splitHeaderFields(string $headerBlock): array
    {
        $fields = [];

        foreach (explode("\r\n", $headerBlock) as $line) {
            if ($line === '') {
                continue;
            }

            if ($fields !== [] && ($line[0] === ' ' || $line[0] === "\t")) {
                $fields[] = array_pop($fields) . "\r\n" . $line;
                continue;
            }

            $fields[] = $line;
        }

        return $fields;
    }

    /**
     * Nette's SendmailMailer passed every argument with PHP_EOL line endings (what the local sendmail expects).
     */
    private static function eol(string $value): string
    {
        return str_replace("\r\n", PHP_EOL, $value);
    }

    /**
     * mail() escapes additional parameters for the shell itself, unusual characters are refused rather than passed on.
     */
    private static function envelopeSenderParameter(string $sender): string
    {
        if (preg_match('/^[A-Za-z0-9._%+=-]+@[A-Za-z0-9.-]+$/', $sender) !== 1) {
            return '';
        }

        return '-f' . $sender;
    }
}
