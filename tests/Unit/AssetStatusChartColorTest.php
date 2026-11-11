<?php

namespace Tests\Unit;

use App\Enums\AssetStatus;
use PHPUnit\Framework\TestCase;

class AssetStatusChartColorTest extends TestCase
{
    public function test_every_status_has_a_hex_chart_color(): void
    {
        foreach (AssetStatus::cases() as $status) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $status->getChartColor(), $status->value);
        }
    }

    public function test_chart_color_follows_the_badge_color_of_the_status(): void
    {
        foreach (AssetStatus::cases() as $status) {
            $this->assertSame(
                AssetStatus::CHART_COLORS[$status->getColor()],
                $status->getChartColor(),
                "{$status->value} must use the chart color of its badge color [{$status->getColor()}]",
            );
        }
    }

    public function test_the_chart_color_table_covers_every_badge_color_in_use(): void
    {
        $used = array_unique(array_map(fn (AssetStatus $status) => $status->getColor(), AssetStatus::cases()));

        foreach ($used as $color) {
            $this->assertArrayHasKey($color, AssetStatus::CHART_COLORS);
        }
    }

    public function test_statuses_with_different_badge_colors_get_different_chart_colors(): void
    {
        $this->assertNotSame(AssetStatus::BALANCE->getChartColor(), AssetStatus::LOST->getChartColor());
        $this->assertNotSame(AssetStatus::REPAIR->getChartColor(), AssetStatus::TRANSFERRING->getChartColor());
        $this->assertSame(AssetStatus::WRITTEN_OFF->getChartColor(), AssetStatus::TRANSFERRED->getChartColor()); // both gray
    }
}
