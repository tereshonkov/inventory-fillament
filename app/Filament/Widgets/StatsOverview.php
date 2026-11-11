<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    /** Asset types shown as tiles, in display order. */
    public const TYPES = ['ПК', 'Ноутбук', 'Робоча станція', 'Монітор', 'ДБЖ', 'БФП'];

    /** Warehouses the stock is counted on. */
    public const WAREHOUSES = ['Склад-327', 'Склад-915'];

    protected static ?int $sort = 1;

    // Stats Overview polls every 5s by default; the numbers do not need it.
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    // Six tiles: two per row on phones instead of tall full-width cards, 3x2 from md, one row from xl.
    protected int|array|null $columns = ['default' => 2, 'md' => 3, 'xl' => 6];

    protected ?string $heading = 'Наявність на складах';

    protected function getDescription(): ?string
    {
        return 'Активи зі статусом «'.AssetStatus::BALANCE->getLabel().'» на складах '
            .implode(' та ', self::WAREHOUSES).'.';
    }

    /**
     * One tile per type, always in the same order. Counts are global (not scoped by role).
     * A single grouped query instead of two queries per tile; types without assets show "0 шт".
     */
    protected function getStats(): array
    {
        $counts = Asset::query()
            ->join('asset_types', 'asset_types.id', '=', 'assets.type_id')
            ->join('locations', 'locations.id', '=', 'assets.location_id')
            ->where('assets.status', AssetStatus::BALANCE)
            ->whereIn('asset_types.name', self::TYPES)
            ->whereIn('locations.name', self::WAREHOUSES)
            ->groupBy('asset_types.name')
            ->selectRaw('asset_types.name as type_name, count(*) as total')
            ->pluck('total', 'type_name');

        return array_map(
            fn (string $type): Stat => Stat::make($type, ((int) ($counts[$type] ?? 0)).' шт'),
            self::TYPES,
        );
    }
}
