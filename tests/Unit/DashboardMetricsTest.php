<?php

namespace Tests\Unit;

use App\Filament\Support\DashboardMetrics;
use PHPUnit\Framework\TestCase;

class DashboardMetricsTest extends TestCase
{
    public function test_bunker_fill_bucket_boundaries_match_dashboard_legend(): void
    {
        $this->assertSame(['0-49%', '50-69%', '70-99%', '100%'], DashboardMetrics::bunkerFillBucketLabels());

        $this->assertSame(0, DashboardMetrics::bunkerFillLevelBucketIndex(49));
        $this->assertSame(1, DashboardMetrics::bunkerFillLevelBucketIndex(50));
        $this->assertSame(1, DashboardMetrics::bunkerFillLevelBucketIndex(69));
        $this->assertSame(2, DashboardMetrics::bunkerFillLevelBucketIndex(70));
        $this->assertSame(2, DashboardMetrics::bunkerFillLevelBucketIndex(99));
        $this->assertSame(3, DashboardMetrics::bunkerFillLevelBucketIndex(100));
    }

    public function test_bunker_fill_badge_colors_match_bucket_boundaries(): void
    {
        $this->assertSame('success', DashboardMetrics::bunkerFillLevelColor(49));
        $this->assertSame('info', DashboardMetrics::bunkerFillLevelColor(50));
        $this->assertSame('info', DashboardMetrics::bunkerFillLevelColor(69));
        $this->assertSame('warning', DashboardMetrics::bunkerFillLevelColor(70));
        $this->assertSame('danger', DashboardMetrics::bunkerFillLevelColor(100));
    }
}
