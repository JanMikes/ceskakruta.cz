<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Mailer;

use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Creates PhpMailTransport for DSN "phpmail://default" (tag `mailer.transport_factory`, PLAN §3.8).
 */
final class PhpMailTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * @return list<string>
     */
    protected function getSupportedSchemes(): array
    {
        throw new \LogicException('Not implemented yet');
    }
}
