<?php

namespace Tests\Feature\Filament;

use App\Enums\AssetStatus;
use App\Filament\Widgets\AssetStatusChart;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class AssetStatusChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function callWidget(string $method)
    {
        $widget = Livewire::test(AssetStatusChart::class)->instance();

        return (new ReflectionMethod($widget, $method))->invoke($widget);
    }

    private function seedStatuses(array $counts): void
    {
        foreach ($counts as $status => $count) {
            Asset::factory()->count($count)->status(AssetStatus::from($status))->create();
        }
    }

    public function test_statuses_are_sorted_from_the_biggest_group_and_only_existing_ones_are_listed(): void
    {
        $this->seedStatuses([
            AssetStatus::REPAIR->value => 2,
            AssetStatus::BALANCE->value => 7,
            AssetStatus::LOST->value => 1,
        ]);

        $data = $this->callWidget('getData');

        $this->assertSame([7, 2, 1], $data['datasets'][0]['data']);
        $this->assertCount(3, $data['labels']);
    }

    public function test_labels_contain_the_status_name_and_its_count(): void
    {
        $this->seedStatuses([
            AssetStatus::BALANCE->value => 7,
            AssetStatus::REPAIR->value => 2,
        ]);

        $labels = $this->callWidget('getData')['labels'];

        $this->assertSame(AssetStatus::BALANCE->getLabel().' — 7', $labels[0]);
        $this->assertSame(AssetStatus::REPAIR->getLabel().' — 2', $labels[1]);
    }

    public function test_bar_colors_are_the_enum_chart_colors_in_the_same_order(): void
    {
        $this->seedStatuses([
            AssetStatus::WRITTEN_OFF->value => 5,
            AssetStatus::BALANCE->value => 3,
            AssetStatus::LOST->value => 1,
        ]);

        $colors = $this->callWidget('getData')['datasets'][0]['backgroundColor'];

        $this->assertSame([
            AssetStatus::WRITTEN_OFF->getChartColor(),
            AssetStatus::BALANCE->getChartColor(),
            AssetStatus::LOST->getChartColor(),
        ], $colors);
    }

    public function test_ties_are_ordered_deterministically(): void
    {
        $this->seedStatuses([
            AssetStatus::REPAIR->value => 2,
            AssetStatus::BALANCE->value => 2,
        ]);

        $first = $this->callWidget('getData')['labels'];
        $second = $this->callWidget('getData')['labels'];

        $this->assertSame($first, $second);
    }

    public function test_empty_database_gives_an_empty_chart(): void
    {
        $data = $this->callWidget('getData');

        $this->assertSame([], $data['labels']);
        $this->assertSame([], $data['datasets'][0]['data']);
    }

    public function test_loads_the_data_with_one_query(): void
    {
        $this->seedStatuses([AssetStatus::BALANCE->value => 2, AssetStatus::LOST->value => 1]);
        $widget = Livewire::test(AssetStatusChart::class)->instance();
        $method = new ReflectionMethod($widget, 'getData');

        DB::enableQueryLog();
        $method->invoke($widget);

        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_is_a_horizontal_bar_chart_without_legend_and_with_whole_number_ticks(): void
    {
        $this->assertSame('bar', $this->callWidget('getType'));

        $options = $this->callWidget('getOptions');

        $this->assertSame('y', $options['indexAxis']);
        $this->assertFalse($options['plugins']['legend']['display']);
        $this->assertSame(0, $options['scales']['x']['ticks']['precision']);
    }
}
