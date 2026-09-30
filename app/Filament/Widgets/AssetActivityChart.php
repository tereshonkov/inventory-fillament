<?php

namespace App\Filament\Widgets;

use App\Models\AssetIncoming;
use App\Models\AssetTransfer;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AssetActivityChart extends ChartWidget
{
    protected ?string $heading = 'Активність за останні 6 місяців';

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => Carbon::now()->subMonths($i));

        $labels = $months->map(fn ($month) => $month->translatedFormat('M Y'))->toArray();

        $incoming = $months->map(fn ($month) => AssetIncoming::whereYear('received_at', $month->year)
            ->whereMonth('received_at', $month->month)
            ->count())->toArray();

        $transferred = $months->map(fn ($month) => AssetTransfer::whereYear('transferred_at', $month->year)
            ->whereMonth('transferred_at', $month->month)
            ->count())->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Прийнято',
                    'data' => $incoming,
                    'backgroundColor' => '#22c55e',
                ],
                [
                    'label' => 'Передано',
                    'data' => $transferred,
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
