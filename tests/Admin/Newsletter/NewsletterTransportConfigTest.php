<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Newsletter;

use CeskaKruta\Web\Services\Mailer\PhpMailTransport;
use CeskaKruta\Web\Tests\Support\IntegrationTestCase;
use Symfony\Component\Mailer\Transport\Transports;

/**
 * PLAN §3.8: mailer transport `newsletter` = env MAILER_NEWSLETTER_DSN, production value phpmail://default
 * handled by PhpMailTransportFactory (tag mailer.transport_factory). Nothing is sent here.
 */
final class NewsletterTransportConfigTest extends IntegrationTestCase
{
    /** @var array{env: mixed, server: mixed}|null */
    private null|array $original = null;

    protected function tearDown(): void
    {
        if ($this->original !== null) {
            if ($this->original['env'] === null) {
                unset($_ENV['MAILER_NEWSLETTER_DSN']);
            } else {
                $_ENV['MAILER_NEWSLETTER_DSN'] = $this->original['env'];
            }

            if ($this->original['server'] === null) {
                unset($_SERVER['MAILER_NEWSLETTER_DSN']);
            } else {
                $_SERVER['MAILER_NEWSLETTER_DSN'] = $this->original['server'];
            }

            $this->original = null;
        }

        parent::tearDown();
    }

    public function testPhpmailDsnBuildsPhpMailTransportForNewsletter(): void
    {
        $this->original = ['env' => $_ENV['MAILER_NEWSLETTER_DSN'] ?? null, 'server' => $_SERVER['MAILER_NEWSLETTER_DSN'] ?? null];
        $_ENV['MAILER_NEWSLETTER_DSN'] = 'phpmail://default';
        $_SERVER['MAILER_NEWSLETTER_DSN'] = 'phpmail://default';

        self::ensureKernelShutdown();
        self::bootKernel();

        $transports = self::getContainer()->get('mailer.transports');

        $property = new \ReflectionProperty(Transports::class, 'transports');
        $configured = $property->getValue($transports);
        self::assertIsArray($configured);

        self::assertArrayHasKey('newsletter', $configured, 'Mailer transport "newsletter" is not configured.');
        self::assertInstanceOf(PhpMailTransport::class, $configured['newsletter']);

        // The other transports stay as they are
        self::assertArrayHasKey('main', $configured);
        self::assertArrayHasKey('orders', $configured);
    }
}
