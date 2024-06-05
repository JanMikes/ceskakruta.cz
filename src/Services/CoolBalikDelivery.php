<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CoolBalikDelivery
{
    public const DELIVERY_PLACE_ID = 21;

    public function __construct(
        readonly private HttpClientInterface $coolbalikClient,
        readonly private CacheInterface $cache,
    ) {
    }

    /**
     * @return array<int, int|null>
     */
    public function getAllowedDaysForPostalCode(string $postalCode): array
    {
        return $this->getMapping()[(int) $postalCode] ?? [];
    }

    public function canDeliverToPostalCode(string $postalCode): bool
    {
        return isset($this->getMapping()[(int) $postalCode]);
    }

    /**
     * @var null|array<int, array<int>>
     */
    private static null|array $mapping = null;

    /**
     * @return array<int, array<int>>
     */
    public function getMapping(): array
    {
        if (self::$mapping === null) {
            $cacheKey = 'coolbalik_postal_codes';

            self::$mapping = $this->cache->get($cacheKey, function (ItemInterface $item): array {
                $item->expiresAt(new \DateTimeImmutable('+1 day'));

                $response = $this->coolbalikClient->request('GET', '/areas');
                $content = $response->getContent();

                /**
                 * @var array<array{
                 *     Days: array<string>,
                 *     CodesArray: array<int>
                 *  }> $json
                 */
                $json = json_decode($content, true);

                return $this->parseResponseToPostalCodesAllowedDays($json);
            });
        }

        return self::$mapping ?? [];
    }

    /**
     * @param array<array{
     *     Days: array<string>,
     *     CodesArray: array<int>
     *  }> $data
     * @return array<int, array<int>>
     */
    private function parseResponseToPostalCodesAllowedDays(array $data): array
    {
        $daysMapping = [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7,
        ];

        /** @var array<int, array<int>> $postalCodes */
        $postalCodes = [];

        foreach ($data as $area) {

            foreach ($area['CodesArray'] as $postalCode) {
                $postalCodes[$postalCode] = [];

                foreach ($area['Days'] as $day) {
                    $postalCodes[$postalCode][] = $daysMapping[$day] ?? -1;
                }
            }

        }

        return $postalCodes;
    }
}
