<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Pohoda;

use CeskaKruta\Web\Exceptions\PohodaRequestFailed;
use CeskaKruta\Web\Value\Admin\AdminOrder;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Pohoda mServer XML API (old `libs/services/Pohoda.php`). Transport errors and non-2xx responses are failures.
 */
readonly final class PohodaClient
{
    private const NAMESPACES = [
        'lAdb' => 'http://www.stormware.cz/schema/version_2/list_addBook.xsd',
        'adb' => 'http://www.stormware.cz/schema/version_2/addressbook.xsd',
        'rdc' => 'http://www.stormware.cz/schema/version_2/documentresponse.xsd',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private PohodaOrderXmlFactory $xmlFactory,
        private LoggerInterface $logger,
        #[Autowire(env: 'POHODA_URL')]
        private string $url,
        #[Autowire(env: 'POHODA_AUTHORIZATION')]
        private string $authorization,
    ) {
    }

    /**
     * @throws PohodaRequestFailed
     */
    public function findAddressId(AdminOrder $order): null|string
    {
        $response = $this->send($this->xmlFactory->searchAddressXml($order));

        return $this->firstValue($response, '//lAdb:addressbook/adb:addressbookHeader/adb:id');
    }

    /**
     * @throws PohodaRequestFailed
     */
    public function createAddress(AdminOrder $order): string
    {
        $response = $this->send($this->xmlFactory->createAddressXml($order));

        return $this->firstValue($response, '//adb:addressbookResponse/rdc:producedDetails/rdc:id')
            ?? throw new PohodaRequestFailed('Pohoda did not return the id of the created address.');
    }

    /**
     * @throws PohodaRequestFailed
     */
    public function send(string $xml): string
    {
        try {
            return $this->httpClient->request('POST', $this->url, [
                'headers' => [
                    'Content-Type' => 'text/xml',
                    'STW-Authorization' => $this->authorization,
                    'User-Agent' => 'CeskaKruta.cz web API',
                ],
                'body' => $xml,
            ])->getContent();
        } catch (ExceptionInterface $exception) {
            $this->logger->error('Pohoda request failed: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);

            throw new PohodaRequestFailed('Pohoda request failed.', previous: $exception);
        }
    }

    /**
     * @throws PohodaRequestFailed
     */
    private function firstValue(string $xml, string $xpath): null|string
    {
        $document = new \DOMDocument();

        if ($xml === '' || !@$document->loadXML($xml)) {
            throw new PohodaRequestFailed('Pohoda response is not a valid XML document.');
        }

        $domXpath = new \DOMXPath($document);
        foreach (self::NAMESPACES as $prefix => $namespace) {
            $domXpath->registerNamespace($prefix, $namespace);
        }

        $nodes = $domXpath->query($xpath);
        $value = $nodes !== false ? trim((string) $nodes->item(0)?->textContent) : '';

        return $value !== '' ? $value : null;
    }
}
