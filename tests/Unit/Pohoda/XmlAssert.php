<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Pohoda;

use PHPUnit\Framework\Assert;

/**
 * Compares Pohoda XML documents 1:1 on content: XML declaration must be identical, the documents are compared in
 * canonical form (C14N) without whitespace-only text nodes and comments, i.e. indentation of the old Latte templates
 * does not matter, every element, attribute, namespace and text (incl. trailing spaces) does.
 */
final class XmlAssert
{
    public const DECLARATION = '<?xml version="1.0" encoding="utf-8"?>';

    public static function assertSameXml(string $expected, string $actual, string $message = ''): void
    {
        Assert::assertStringStartsWith(self::DECLARATION, $actual, 'XML declaration differs. ' . $message);
        Assert::assertSame(self::canonical($expected), self::canonical($actual), $message);
    }

    public static function canonical(string $xml): string
    {
        $document = new \DOMDocument();
        $document->preserveWhiteSpace = false;

        if (!@$document->loadXML($xml)) {
            Assert::fail("Not a well-formed XML document:\n" . $xml);
        }

        $canonical = (string) $document->C14N(false, false);

        // One element per line → readable diffs on failure
        return str_replace('><', ">\n<", $canonical);
    }

    /**
     * Text of the first element matching the XPath (Pohoda namespaces dat/ord/typ/adb/lAdb/ftr registered).
     */
    public static function text(string $xml, string $xpath): null|string
    {
        $document = new \DOMDocument();
        $document->loadXML($xml);

        $domXpath = new \DOMXPath($document);
        foreach ([
            'dat' => 'http://www.stormware.cz/schema/version_2/data.xsd',
            'ord' => 'http://www.stormware.cz/schema/version_2/order.xsd',
            'typ' => 'http://www.stormware.cz/schema/version_2/type.xsd',
            'adb' => 'http://www.stormware.cz/schema/version_2/addressbook.xsd',
            'lAdb' => 'http://www.stormware.cz/schema/version_2/list_addBook.xsd',
            'ftr' => 'http://www.stormware.cz/schema/version_2/filter.xsd',
        ] as $prefix => $namespace) {
            $domXpath->registerNamespace($prefix, $namespace);
        }

        $nodes = $domXpath->query($xpath);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        return $nodes->item(0)?->textContent;
    }
}
