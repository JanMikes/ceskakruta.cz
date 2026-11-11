<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Integration\Cart;

use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Tests\Support\IntegrationTestCase;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * `place_closed_day.is_open = 1` ("Otevřeno navíc") makes a normally closed weekday orderable.
 * Fixture place 1 (Rychvald) is open only on Tuesday (1 day before) and Friday (2 days before).
 */
final class ExtraOpeningDayTest extends IntegrationTestCase
{
    private const RYCHVALD = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get(RequestStack::class)->push($request);
    }

    public function testNormallyClosedWeekdayIsNotAvailable(): void
    {
        self::assertFalse($this->cart()->isDateAvailable($this->nextMonday(), self::RYCHVALD));
    }

    public function testExtraOpeningDayMakesWeekdayAvailable(): void
    {
        $monday = $this->nextMonday();
        $this->exception(self::RYCHVALD, $monday, isOpen: true, daysBefore: 1);

        self::assertTrue($this->cart()->isDateAvailable($monday, self::RYCHVALD));
        self::assertFalse($this->cart()->isDateAvailable($monday->modify('+7 days'), self::RYCHVALD), 'Only the given date is opened.');
    }

    public function testExtraOpeningDayForAllPlaces(): void
    {
        $monday = $this->nextMonday();
        $this->exception(null, $monday, isOpen: true, daysBefore: 1);

        self::assertTrue($this->cart()->isDateAvailable($monday, self::RYCHVALD));
    }

    public function testDaysBeforeOfExtraOpeningDayIsRespected(): void
    {
        $monday = $this->nextMonday();
        $daysUntil = (int) (new DateTimeImmutable('today'))->diff($monday)->days;
        $this->exception(self::RYCHVALD, $monday, isOpen: true, daysBefore: $daysUntil + 1);

        self::assertFalse($this->cart()->isDateAvailable($monday, self::RYCHVALD), 'Ordering deadline already passed.');
    }

    public function testClosedDayWinsOverExtraOpeningDay(): void
    {
        $monday = $this->nextMonday();
        $this->exception(null, $monday, isOpen: true, daysBefore: 1);
        $this->exception(self::RYCHVALD, $monday, isOpen: false);

        self::assertFalse($this->cart()->isDateAvailable($monday, self::RYCHVALD));
    }

    public function testExtraOpeningDayIsNotAClosedDay(): void
    {
        // Tuesday is a normal opening day, an "open" exception must not close it
        $tuesday = $this->nextMonday()->modify('+1 day');
        $this->exception(self::RYCHVALD, $tuesday, isOpen: true, daysBefore: 1);

        self::assertTrue($this->cart()->isDateAvailable($tuesday, self::RYCHVALD));
    }

    private function cart(): CartService
    {
        return self::getContainer()->get(CartService::class);
    }

    /**
     * A Monday at least 7 days ahead, so the usual "days before" never blocks it.
     */
    private function nextMonday(): DateTimeImmutable
    {
        return (new DateTimeImmutable('today'))->modify('+7 days')->modify('next monday');
    }

    private function exception(null|int $placeId, DateTimeImmutable $date, bool $isOpen, null|int $daysBefore = null): void
    {
        $this->connection()->insert('place_closed_day', [
            'place_id' => $placeId,
            'date' => $date->format('Y-m-d'),
            'is_open' => $isOpen ? 1 : 0,
            'days_before' => $daysBefore,
            'ins_dt' => '2024-01-01 00:00:00',
        ]);
    }
}
