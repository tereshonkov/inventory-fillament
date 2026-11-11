<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AssetActivityChart;
use App\Filament\Widgets\AssetsNeedingAction;
use App\Filament\Widgets\AssetStatusChart;
use App\Filament\Widgets\ComponentsStockOverview;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Огляд майна';

    protected static ?string $navigationLabel = 'Огляд майна';

    /**
     * Widgets in decision order: current numbers (assets, then components on the warehouses), then the trend, then the distribution, then the queue of assets that need action.
     * The order is explicit instead of relying on the discovery order / equal $sort values.
     */
    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            ComponentsStockOverview::class,
            AssetActivityChart::class,
            AssetStatusChart::class,
            AssetsNeedingAction::class,
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
