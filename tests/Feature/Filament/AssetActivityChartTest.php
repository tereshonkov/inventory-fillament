<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\AssetActivityChart;
use App\Models\Asset;
use App\Models\AssetIncoming;
use App\Models\AssetTransfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class AssetActivityChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());

        Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function widget(): AssetActivityChart
    {
        return Livewire::test(AssetActivityChart::class)->instance();
    }

    /** @return mixed */
    private function callWidget(string $method)
    {
        $widget = $this->widget();

        return (new ReflectionMethod($widget, $method))->invoke($widget);
    }

    /** @return array{datasets: array, labels: array} */
    private function data(): array
    {
        return $this->callWidget('getData');
    }

    private function incoming(string $date, int $count = 1): void
    {
        AssetIncoming::factory()->count($count)->create(['asset_id' => Asset::factory(), 'received_at' => $date]);
    }

    private function transfer(string $date, int $count = 1): void
    {
        AssetTransfer::factory()->count($count)->create(['asset_id' => Asset::factory(), 'transferred_at' => $date]);
    }

    public function test_shows_six_months_oldest_first_ending_with_the_current_one(): void
    {
        $labels = $this->data()['labels'];

        $this->assertCount(6, $labels);
        $this->assertCount(6, array_unique($labels));
        $this->assertSame(Carbon::parse('2026-06-01')->translatedFormat('M Y'), $labels[0]);
        $this->assertSame(Carbon::parse('2026-11-01')->translatedFormat('M Y'), $labels[5]);
    }

    public function test_counts_documents_per_month_including_the_boundaries(): void
    {
        $this->incoming('2026-06-01');                // first day of the first month
        $this->incoming('2026-05-31', 4);             // before the period
        $this->incoming('2026-08-10', 2);
        $this->incoming('2026-11-30');                // last day of the current month
        $this->incoming('2026-12-01', 3);             // after the period

        $this->transfer('2026-06-30');
        $this->transfer('2026-09-05', 3);
        $this->transfer('2026-11-01');

        $datasets = $this->data()['datasets'];

        $this->assertSame([1, 0, 2, 0, 0, 1], $datasets[0]['data']);
        $this->assertSame([1, 0, 0, 3, 0, 1], $datasets[1]['data']);
    }

    public function test_months_without_documents_are_zero_integers(): void
    {
        foreach ($this->data()['datasets'] as $dataset) {
            $this->assertSame([0, 0, 0, 0, 0, 0], $dataset['data']);
        }
    }

    public function test_uses_one_query_per_series(): void
    {
        $this->incoming('2026-10-10', 3);
        $this->transfer('2026-10-11', 2);
        $widget = $this->widget();
        $method = new ReflectionMethod($widget, 'getData');

        DB::enableQueryLog();
        $method->invoke($widget);

        $this->assertCount(2, DB::getQueryLog());
    }

    public function test_is_a_line_chart_with_whole_number_ticks_from_zero(): void
    {
        $this->assertSame('line', $this->callWidget('getType'));

        $options = $this->callWidget('getOptions');

        $this->assertTrue($options['scales']['y']['beginAtZero']);
        $this->assertSame(0, $options['scales']['y']['ticks']['precision']);
    }

    public function test_explains_that_documents_are_counted_not_assets(): void
    {
        Livewire::test(AssetActivityChart::class)
            ->assertSee('Активність за останні 6 місяців')
            ->assertSee('документів')
            ->assertSee('не активів');
    }
}
