<?php

namespace App\Filament\Widgets;

use App\Enums\ComponentKind;
use App\Models\AssetComponent;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Комплектуючі робочих станцій, що лежать на складах, окремо від плиток самостійних активів. */
class ComponentsStockOverview extends StatsOverviewWidget
{
    /** Kinds shown as tiles, in display order. */
    public const KINDS = [
        ComponentKind::PC,
        ComponentKind::MONITOR,
        ComponentKind::LAPTOP,
        ComponentKind::UPS,
        ComponentKind::PRINTER,
    ];

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = ['default' => 2, 'md' => 3, 'xl' => 5];

    protected ?string $heading = 'Комплектуючі на складах';

    protected function getDescription(): ?string
    {
        return 'Окремі одиниці техніки зі складу робочих станцій на '
            .implode(' та ', StatsOverview::WAREHOUSES).' (справні й несправні).';
    }

    /** One grouped query; a tile is always shown, "0 шт" when there are no units. */
    protected function getStats(): array
    {
        $counts = AssetComponent::query()
            ->join('locations', 'locations.id', '=', 'asset_components.location_id')
            ->whereIn('locations.name', StatsOverview::WAREHOUSES)
            ->whereIn('asset_components.kind', array_map(fn (ComponentKind $kind): string => $kind->value, self::KINDS))
            ->groupBy('asset_components.kind')
            ->selectRaw('asset_components.kind as kind, count(*) as total')
            ->pluck('total', 'kind');

        return array_map(
            fn (ComponentKind $kind): Stat => Stat::make(
                $kind->getShortLabel().' (комплектуючі)',
                ((int) ($counts[$kind->value] ?? 0)).' шт',
            ),
            self::KINDS,
        );
    }
}
