<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Vendor;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * PLAN §2.13 / old app/BackendModule/ManageVendorFormFactory.php: all fields optional, text max 50 (zip max 5),
 * `email` validated only when filled.
 */
final class CreateVendorTest extends VendorTestCase
{
    public function testCreates(): void
    {
        $crawler = $this->openPage(self::LIST_PATH . '/pridat');
        $this->submitAdminForm($crawler, 'vendor', self::validValues());

        $id = $this->lastInsertedId('vendor');
        self::assertGreaterThan(self::FIXTURE_ID, $id);
        $this->assertRedirectsTo(self::LIST_PATH, $id);

        $row = $this->fetchRow('vendor', $id);
        foreach (self::validValues() as $column => $value) {
            self::assertSame($value, $row[$column], sprintf('Column "%s".', $column));
        }
        self::assertInsertAudit($row);

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }

    public function testOrderSetAfterInsert(): void
    {
        $crawler = $this->openPage(self::LIST_PATH . '/pridat');
        $this->submitAdminForm($crawler, 'vendor', self::validValues());

        $id = $this->lastInsertedId('vendor');
        $this->assertRedirectsTo(self::LIST_PATH, $id);
        self::assertSame($id * 10, self::intValue($this->fetchRow('vendor', $id)['order']));
    }

    public function testAllFieldsAreOptional(): void
    {
        $this->assertCreateOutcome(array_map(static fn (): string => '', self::validValues()), true);
    }

    #[DataProvider('provideEmails')]
    public function testEmailValidation(string $email, bool $valid): void
    {
        $this->assertCreateOutcome(['email' => $email], $valid);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideEmails(): iterable
    {
        yield 'valid' => ['prodejce@example.test', true];
        yield 'empty (optional)' => ['', true];
        yield 'missing at' => ['prodejce.example.test', false];
        yield 'missing domain' => ['prodejce@', false];
        yield 'with spaces' => ['pro dejce@example.test', false];
    }

    #[DataProvider('provideMaxLengths')]
    public function testMaxLengths(string $field, int $maxLength): void
    {
        $this->assertCreateOutcome([$field => str_repeat('1', $maxLength)], true);
        $this->assertCreateOutcome([$field => str_repeat('1', $maxLength + 1)], false);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideMaxLengths(): iterable
    {
        yield 'place' => ['place', 50];
        yield 'name' => ['name', 50];
        yield 'address' => ['address', 50];
        yield 'city' => ['city', 50];
        yield 'zip' => ['zip', 5];
        yield 'web' => ['web', 50];
        yield 'phone' => ['phone', 50];
    }

    public function testCreatePageHasNoSaveAndContinueButton(): void
    {
        $crawler = $this->openPage(self::LIST_PATH . '/pridat');

        $this->formByButton($crawler);
        self::assertSame([], array_values(array_filter(
            $crawler->filter('button')->each(static fn (Crawler $node): string => trim($node->text(''))),
            static fn (string $label): bool => $label === self::SAVE_AND_CONTINUE,
        )));
    }
}
