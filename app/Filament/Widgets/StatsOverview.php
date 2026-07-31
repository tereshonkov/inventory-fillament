<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetType;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = [
            Stat::make('Не введено в експлуатацію', Asset::where('status', AssetStatus::NOT_PUT_IN_TO_OPERATION)->count() . ' шт'),
        ];

        foreach (AssetType::all() as $type) {
            $stats[] = Stat::make(
                $type->name,
                Asset::where('type_id', $type->id)
                    ->where('status', AssetStatus::BALANCE)
                    ->whereHas('location', fn($q) => $q->whereIn('name', ['Склад-327', 'склад-915']))
                    ->count() . ' шт'
            );
        }

        return $stats;
    }
}
