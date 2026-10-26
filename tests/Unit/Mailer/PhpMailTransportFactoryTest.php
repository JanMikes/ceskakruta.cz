<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Mailer;

use CeskaKruta\Web\Services\Mailer\PhpMailTransport;
use CeskaKruta\Web\Services\Mailer\PhpMailTransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\Dsn;

final class PhpMailTransportFactoryTest extends TestCase
{
    public function testSupportsPhpmailScheme(): void
    {
        $factory = new PhpMailTransportFactory();

        self::assertTrue($factory->supports(Dsn::fromString('phpmail://default')));
        self::assertFalse($factory->supports(Dsn::fromString('smtp://localhost:25')));
        self::assertFalse($factory->supports(Dsn::fromString('null://null')));
    }

    public function testCreatesPhpMailTransport(): void
    {
        $transport = (new PhpMailTransportFactory())->create(Dsn::fromString('phpmail://default'));

        self::assertInstanceOf(PhpMailTransport::class, $transport);
    }

    public function testRejectsOtherSchemes(): void
    {
        $this->expectException(UnsupportedSchemeException::class);

        (new PhpMailTransportFactory())->create(Dsn::fromString('smtp://localhost:25'));
    }
}
