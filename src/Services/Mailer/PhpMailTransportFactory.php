<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Mailer;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Creates PhpMailTransport for DSN "phpmail://default" (tag `mailer.transport_factory`, PLAN §3.8).
 */
#[AutoconfigureTag('mailer.transport_factory')]
final class PhpMailTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        if ($dsn->getScheme() !== 'phpmail') {
            throw new UnsupportedSchemeException($dsn, 'phpmail', $this->getSupportedSchemes());
        }

        return new PhpMailTransport(null, $this->dispatcher, $this->logger);
    }

    /**
     * @return list<string>
     */
    protected function getSupportedSchemes(): array
    {
        return ['phpmail'];
    }
}
