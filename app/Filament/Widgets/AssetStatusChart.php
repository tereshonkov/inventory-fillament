<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Filament\Widgets\ChartWidget;

class AssetStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 1];

    protected ?string $heading = 'Розподіл за статусом';

    // Widgets poll every 5s by default; this data does not change that fast (and the query runs on every poll).
    protected ?string $pollingInterval = null;

    /**
     * One query. Statuses without assets are not listed; the biggest group comes first.
     * Colors come from the enum, so a status looks the same here as in its badge.
     */
    protected function getData(): array
    {
        $counts = Asset::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->orderBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($counts as $value => $total) {
            $status = AssetStatus::tryFrom($value);

            if (! $status) {
                continue;
            }

            // The count is part of the label, so the value is readable without hovering.
            $labels[] = $status->getLabel().' — '.$total;
            $data[] = (int) $total;
            $colors[] = $status->getChartColor();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Активів',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            // Horizontal bars: readable for any number of statuses, unlike a doughnut with one dominant slice.
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
                'y' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }
}
