<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Components\Admin;

use CeskaKruta\Web\Query\Admin\GetDashboardTrends;
use CeskaKruta\Web\Value\Admin\Dashboard\TopProduct;
use Psr\Clock\ClockInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Best selling products by revenue over a selectable period (orders created in the last N days).
 */
#[AsLiveComponent(name: 'Admin:DashboardTopProducts', template: 'components/Admin/DashboardTopProducts.html.twig', route: 'admin_live_component')]
final class DashboardTopProducts
{
    use DefaultActionTrait;

    public const PERIODS = [30 => '30 dní', 90 => '90 dní', 365 => 'Rok'];

    private const LIMIT = 10;

    #[LiveProp(writable: true, url: true)]
    public int $days = 30;

    public function __construct(
        readonly private GetDashboardTrends $getDashboardTrends,
        readonly private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<TopProduct>
     */
    public function products(): array
    {
        $days = array_key_exists($this->days, self::PERIODS) ? $this->days : 30;

        return $this->getDashboardTrends->topProducts($this->clock->now()->setTime(0, 0)->modify(sprintf('-%d days', $days)), self::LIMIT);
    }

    /**
     * @return array<int, string>
     */
    public function periods(): array
    {
        return self::PERIODS;
    }
}
