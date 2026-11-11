<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AssetActivityChart;
use App\Filament\Widgets\AssetStatusChart;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Огляд майна';

    protected static ?string $navigationLabel = 'Огляд майна';

    /**
     * Widgets in decision order: current numbers, then the trend, then the distribution.
     * The order is explicit instead of relying on the discovery order / equal $sort values.
     */
    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            AssetActivityChart::class,
            AssetStatusChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }
}
