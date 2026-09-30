<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Filament\Widgets\ChartWidget;

class AssetStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Розподіл за статусом';

    // protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $counts = Asset::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $labels = [];
        $data = [];
        $colors = [];

        $colorMap = [
            AssetStatus::BALANCE->value => '#22c55e',
            AssetStatus::NOT_PUT_IN_TO_OPERATION->value => '#f97316',
            AssetStatus::CAPITALIZE->value => '#3b82f6',
            AssetStatus::TRANSFERRING->value => '#a855f7',
            AssetStatus::TRANSFERRED->value => '#64748b',
            AssetStatus::WRITTEN_OFF->value => '#ef4444',
            AssetStatus::WRITING_OFF->value => '#eab308',
            AssetStatus::LOST->value => '#991b1b',
            AssetStatus::REPAIR->value => '#06b6d4',
        ];

        foreach ($counts as $status => $count) {
            $enum = AssetStatus::from($status);
            $labels[] = $enum->getLabel();
            $data[] = $count;
            $colors[] = $colorMap[$status] ?? '#9ca3af';
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
