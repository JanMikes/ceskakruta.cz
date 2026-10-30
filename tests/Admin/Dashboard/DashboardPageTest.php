<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Dashboard;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

final class DashboardPageTest extends AdminWebTestCase
{
    use InteractsWithLiveComponents;

    public function testDashboardShowsQuickActionsAndInsights(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['Objednávky', 'Přidat aktualitu', 'Přidat výjimku (zavřeno)', 'Přidat produkt', 'Rozkrmená krůťata', 'Chlazené krůty'],
            $crawler->filter('.admin-quick-link')->each(static fn ($link): string => trim($link->text())),
        );

        foreach (['Ke kontrole', 'Přijaté objednávky', 'Expedice'] as $heading) {
            self::assertSelectorTextContains('main', $heading);
        }

        // Quick overview only: day cards link to "Co připravit", details live on the other pages
        self::assertCount(2, $crawler->filter('a.admin-dash-day[href^="/admin/co-pripravit?day="]'));
        self::assertCount(7, $crawler->filter('a.admin-dash-strip-day'));
        self::assertCount(1, $crawler->filter('a[href="/admin/pravidelne-objednavky"].admin-dash-check'));
        self::assertCount(0, $crawler->filter('canvas, [data-live-name-value]'));
    }

    public function testPreparationPage(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/co-pripravit');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-live-name-value="Admin:DashboardOperations"]'));
        self::assertSelectorTextContains('main', 'Co připravit –');
    }

    public function testRecurringOrdersPage(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/pravidelne-objednavky');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'Pravidelné objednávky');
    }

    public function testStatisticsPage(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/statistiky');

        self::assertResponseIsSuccessful();

        foreach (['Tržby podle kanálu', 'Počet objednávek', 'Nejprodávanější produkty'] as $heading) {
            self::assertSelectorTextContains('main', $heading);
        }

        self::assertCount(2, $crawler->filter('canvas[data-controller~="symfony--ux-chartjs--chart"]'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dashboardUrls(): iterable
    {
        yield 'home' => ['/admin'];
        yield 'preparation' => ['/admin/co-pripravit'];
        yield 'recurring orders' => ['/admin/pravidelne-objednavky'];
        yield 'statistics' => ['/admin/statistiky'];
    }

    #[DataProvider('dashboardUrls')]
    public function testQueryCountDoesNotGrowWithData(string $url): void
    {
        $this->loginAsAdmin();
        DashboardOrdersFixture::insert($this->connection());
        $this->recurringOrderForToday(4004);
        $this->dashboardQueryCount($url); // warm-up: login and arrange queries are logged in the same kernel
        $before = $this->dashboardQueryCount($url);

        // More orders on many days/places and more recurring customers must not add queries (no N+1)
        for ($i = 0; $i < 5; $i++) {
            DashboardOrdersFixture::insert($this->connection());
        }

        foreach ([4005, 2, 3, 4003] as $userId) {
            $this->recurringOrderForToday($userId);
        }

        self::assertSame($before, $this->dashboardQueryCount($url));
    }

    public function testLiveComponentsAreBehindTheAdminFirewall(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/co-pripravit');
        $url = $crawler->filter('[data-live-name-value="Admin:DashboardOperations"]')->attr('data-live-url-value');

        self::assertStringStartsWith('/admin/_components/Admin:DashboardOperations', (string) $url);

        $this->client->restart();
        $this->client->request('GET', '/admin/_components/Admin:DashboardOperations');

        self::assertResponseRedirects('/admin/login');
    }

    public function testOperationsReRenderForSelectedDayAndBasis(): void
    {
        $orders = DashboardOrdersFixture::insert($this->connection());
        $this->loginAsAdmin();

        $component = $this->createLiveComponent('Admin:DashboardOperations', client: $this->client)->call('selectDay', ['day' => '2030-03-14']);
        $crawler = $component->render()->crawler();

        // Expedition: CoolBalík A (customer Friday) + own delivery B
        self::assertStringContainsString('Co připravit – Čt 14. 3. 2030', $crawler->text());
        self::assertCount(2, $crawler->filter('.admin-dash-orders li'));
        self::assertStringContainsString('#' . $orders['A'], $crawler->filter('.admin-dash-orders')->text());
        self::assertSame('Březen 2030', $crawler->filter('.admin-dash-calendar-head h2')->text());

        $crawler = $component->set('basis', 'customer')->render()->crawler();

        // Customer day: only B is delivered on Thursday
        self::assertCount(1, $crawler->filter('.admin-dash-orders li'));
        self::assertStringContainsString('#' . $orders['B'], $crawler->filter('.admin-dash-orders')->text());
    }

    public function testTopProductsPeriodSwitch(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->createLiveComponent('Admin:DashboardTopProducts', client: $this->client)->set('days', 365)->render()->crawler();

        self::assertSame('365', $crawler->filter('input[data-model="days"][checked]')->attr('value'));
    }

    private function recurringOrderForToday(int $userId): void
    {
        $id = $this->uuid();
        $this->connection()->insert('recurring_order', ['id' => $id, 'user_id' => $userId, 'day_of_week' => (int) date('N')]);
        $this->connection()->insert('recurring_order_item', [
            'id' => $this->uuid(),
            'order_id' => $id,
            'product_id' => 100,
            'packages' => '[]',
            'other_package_size_amount' => 1,
            'note' => '',
            'is_sliced' => 0,
            'is_packed' => 0,
        ]);
    }

    private function uuid(): string
    {
        $uuid = $this->connection()->fetchOne('SELECT UUID()');
        self::assertIsString($uuid);

        return $uuid;
    }

    private function dashboardQueryCount(string $url): int
    {
        $this->client->enableProfiler();
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();

        $profile = $this->client->getProfile();
        self::assertInstanceOf(Profile::class, $profile);
        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);

        // The collector also sees the arrange inserts of this test (same kernel), count only the page reads
        $reads = array_filter(
            $collector->getQueries()['default'] ?? [],
            static fn (array $query): bool => preg_match('/^\s*SELECT (?!UUID\(\))/i', (string) $query['sql']) === 1,
        );

        return count($reads);
    }

    public function testOrderListFiltersOrdersMissingInBoxxi(): void
    {
        $orders = DashboardOrdersFixture::insert($this->connection());

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/objednavky?' . http_build_query(['missing' => 'boxxi', 'date_from' => '2030-03-01']));

        self::assertResponseIsSuccessful();
        self::assertSame(['item-' . $orders['A']], $crawler->filter('tr[id^="item-"]')->each(static fn ($row): string => (string) $row->attr('id')));
        self::assertSame('boxxi', $crawler->filter('#filter-missing option[selected]')->attr('value'));
    }
}
