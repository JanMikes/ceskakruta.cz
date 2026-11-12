<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Boxxi;

use CeskaKruta\Web\Exceptions\BoxxiRequestFailed;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Boxxi (CoolBalík) shipment API calls, spec: https://api.boxxi-logistics.cz/swagger/v1/swagger.json.
 * A transport error or non-2xx response throws `BoxxiRequestFailed` (logged), so the caller's DB transaction rolls back.
 */
readonly final class BoxxiShipments
{
    public const CANCEL_REASON = 'Zrušeno v administraci ceskakruta.cz';

    public function __construct(
        private HttpClientInterface $coolbalikClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * `POST /v1/shipments`
     *
     * @param array<string, mixed> $payload
     * @return string raw response (contains `ShipmentNumber`)
     * @throws BoxxiRequestFailed
     */
    public function create(array $payload): string
    {
        return $this->request('/v1/shipments', $payload);
    }

    /**
     * `POST /v1/shipments/{shipmentNumber}/update`
     *
     * @param array<string, mixed> $payload
     * @throws BoxxiRequestFailed
     */
    public function update(string $shipmentNumber, array $payload): void
    {
        $this->request('/v1/shipments/' . rawurlencode($shipmentNumber) . '/update', $payload);
    }

    /**
     * `POST /v1/shipments/{shipmentNumber}/cancel`
     *
     * @throws BoxxiRequestFailed
     */
    public function cancel(string $shipmentNumber): void
    {
        $this->request('/v1/shipments/' . rawurlencode($shipmentNumber) . '/cancel', ['Reason' => self::CANCEL_REASON]);
    }

    /**
     * @param array<string, mixed> $payload
     * @throws BoxxiRequestFailed
     */
    private function request(string $path, array $payload): string
    {
        try {
            return $this->coolbalikClient->request('POST', $path, [
                'headers' => ['Content-Type' => 'application/json'],
                // Plain json_encode like the old app (HttpClient's `json` option would send 22.0 instead of 22)
                'body' => json_encode($payload, JSON_THROW_ON_ERROR),
            ])->getContent();
        } catch (ExceptionInterface $exception) {
            $this->logger->error('Boxxi request {path} failed: {message}', ['path' => $path, 'message' => $exception->getMessage(), 'exception' => $exception]);

            throw new BoxxiRequestFailed('Boxxi request failed.', previous: $exception);
        }
    }
}
