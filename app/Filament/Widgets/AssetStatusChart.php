<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class AssetStatusChart extends ChartWidget
{
    /** Thin pill-shaped bars; the radius is capped by Chart.js at half of the thickness. */
    public const BAR_THICKNESS = 12;

    /** Light track behind every bar (works on light and dark themes). */
    public const TRACK_COLOR = 'rgba(107, 114, 128, 0.15)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 1];

    protected ?string $heading = 'Розподіл за статусом';

    // One short line, like the neighbouring chart, so both card headers have the same height.
    protected ?string $description = 'Кількість активів у кожному статусі.';

    // Widgets poll every 5s by default; this data does not change that fast (and the query runs on every poll).
    protected ?string $pollingInterval = null;

    /** @var Collection<string, int>|null status value => number of assets, loaded once per request */
    private ?Collection $counts = null;

    /**
     * One query (shared by data and options). Statuses without assets are not listed; the biggest group is first.
     *
     * @return Collection<string, int>
     */
    private function counts(): Collection
    {
        return $this->counts ??= Asset::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->orderBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total);
    }

    /**
     * Each status is a coloured pill laid over a light track that spans the whole width:
     * the track is the total number of assets, the pill is the share of the status.
     * Colors come from the enum, so a status looks the same here as in its badge.
     */
    protected function getData(): array
    {
        $labels = [];
        $data = [];
        $colors = [];

        foreach ($this->counts() as $value => $total) {
            $status = AssetStatus::tryFrom($value);

            if (! $status) {
                continue;
            }

            // The count is part of the label, so the value is readable without hovering.
            $labels[] = $status->getLabel().' — '.$total;
            $data[] = $total;
            $colors[] = $status->getChartColor();
        }

        $overall = array_sum($data);

        $pill = [
            'borderRadius' => 999,
            'borderSkipped' => false,
            'barThickness' => self::BAR_THICKNESS,
            // Draw the bars on top of each other instead of side by side.
            'grouped' => false,
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Активів',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'order' => 1, // drawn above the track
                ] + $pill,
                [
                    'label' => 'Усього',
                    'data' => array_fill(0, count($data), $overall),
                    'backgroundColor' => self::TRACK_COLOR,
                    'order' => 2, // drawn first (behind)
                ] + $pill,
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
        $overall = $this->counts()->sum();

        return [
            // Horizontal bars: readable for any number of statuses, unlike a doughnut with one dominant slice.
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
                // The counts are in the labels; the track would only add a confusing "Усього" tooltip.
                'tooltip' => ['enabled' => false],
            ],
            'scales' => [
                'x' => array_filter([
                    // The scale ends exactly at the total, so the track reaches the right edge.
                    'min' => 0,
                    'max' => $overall > 0 ? $overall : null,
                    'display' => false,
                    'grid' => ['display' => false],
                ], fn ($value) => $value !== null),
                'y' => [
                    'grid' => ['display' => false],
                    'border' => ['display' => false],
                ],
            ],
        ];
    }
}
