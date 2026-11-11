<?php

namespace App\Filament\Widgets;

use App\Models\AssetIncoming;
use App\Models\AssetTransfer;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AssetActivityChart extends ChartWidget
{
    /** Number of months shown, including the current one. */
    public const MONTHS = 6;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 1];

    protected ?string $heading = 'Активність за останні 6 місяців';

    protected ?string $description = 'Кількість документів, а не активів.';

    // Widgets poll every 5s by default; this data does not change that fast (and the query runs on every poll).
    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $months = collect(range(self::MONTHS - 1, 0))
            ->map(fn (int $i): Carbon => Carbon::now()->startOfMonth()->subMonths($i));

        $from = $months->first();
        $to = Carbon::now()->endOfMonth();

        $incoming = $this->countByMonth(AssetIncoming::class, 'received_at', $from, $to);
        $transferred = $this->countByMonth(AssetTransfer::class, 'transferred_at', $from, $to);

        return [
            'datasets' => [
                [
                    'label' => 'Надходження',
                    'data' => $months->map(fn (Carbon $month): int => $incoming[$month->format('Y-m')] ?? 0)->all(),
                    'borderColor' => '#22c55e',
                    'backgroundColor' => '#22c55e',
                    'tension' => 0, // straight segments: smoothing would invent values between the monthly counts
                ],
                [
                    'label' => 'Передачі',
                    'data' => $months->map(fn (Carbon $month): int => $transferred[$month->format('Y-m')] ?? 0)->all(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => '#3b82f6',
                    'tension' => 0, // straight segments: smoothing would invent values between the monthly counts
                ],
            ],
            'labels' => $months->map(fn (Carbon $month): string => $month->translatedFormat('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    // Counts of documents are whole numbers: no 0.1 / 0.2 ticks.
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }

    /**
     * One query per series; grouping by month happens in PHP so the code works the same on SQLite and MySQL.
     *
     * @param  class-string<Model>  $model
     * @return Collection<string, int> "Y-m" => count
     */
    private function countByMonth(string $model, string $column, Carbon $from, Carbon $to): Collection
    {
        return $model::query()
            ->whereDate($column, '>=', $from->toDateString())
            ->whereDate($column, '<=', $to->toDateString())
            ->pluck($column)
            ->countBy(fn ($date): string => Carbon::parse($date)->format('Y-m'));
    }
}
