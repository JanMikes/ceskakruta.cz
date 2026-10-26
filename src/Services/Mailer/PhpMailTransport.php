<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Mailer;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Sends messages through PHP's mail() (the hosting's server-configured delivery), like Nette's SendmailMailer did.
 * DSN: phpmail://default (see PhpMailTransportFactory, PLAN §3.8).
 *
 * mail() receives the encoded "To" header value, the encoded "Subject" header value, the encoded body and the remaining
 * headers (without To/Subject); the envelope sender is passed as additional parameter "-f<sender>".
 */
final class PhpMailTransport extends AbstractTransport
{
    /**
     * Same signature as mail(): (to, subject, message, additional_headers, additional_params) → bool.
     *
     * @var \Closure(string, string, string, string, string): bool
     */
    private \Closure $mail; // @phpstan-ignore property.onlyWritten (read by doSend() once implemented)

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
        throw new \LogicException('Not implemented yet');
    }

    protected function doSend(SentMessage $message): void
    {
        throw new \LogicException('Not implemented yet');
    }
}
