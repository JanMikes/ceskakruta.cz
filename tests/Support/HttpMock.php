<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Support;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Every HTTP client in the test environment (incl. scoped clients for Boxxi and Pohoda) goes through this factory,
 * so no test can ever reach a real API. Responses are queued per test, unexpected requests fail loudly.
 *
 * State is static on purpose: the test client reboots the kernel between requests (new service instance).
 */
final class HttpMock
{
    /** @var list<MockResponse> */
    private static array $responses = [];

    /** @var list<array{method: string, url: string, body: string, headers: list<string>}> */
    private static array $requests = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __invoke(string $method, string $url, array $options): ResponseInterface
    {
        $body = $options['body'] ?? '';
        $headers = $options['headers'] ?? [];

        self::$requests[] = [
            'method' => $method,
            'url' => $url,
            'body' => is_string($body) ? $body : '',
            'headers' => is_array($headers) ? array_values(array_filter($headers, 'is_string')) : [],
        ];

        $response = array_shift(self::$responses);

        if ($response === null) {
            throw new \LogicException(sprintf('Unexpected HTTP request in test: %s %s (queue a response with HttpMock::queue()).', $method, $url));
        }

        return $response;
    }

    public static function queue(MockResponse ...$responses): void
    {
        foreach ($responses as $response) {
            self::$responses[] = $response;
        }
    }

    /**
     * @return list<array{method: string, url: string, body: string, headers: list<string>}>
     */
    public static function requests(): array
    {
        return self::$requests;
    }

    public static function reset(): void
    {
        self::$responses = [];
        self::$requests = [];
    }
}
