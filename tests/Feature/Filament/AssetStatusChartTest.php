<?php

namespace Tests\Feature\Filament;

use App\Enums\AssetStatus;
use App\Filament\Widgets\AssetStatusChart;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionClass;
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

    public function test_data_and_options_are_built_from_a_single_query(): void
    {
        $this->seedStatuses([AssetStatus::BALANCE->value => 2, AssetStatus::LOST->value => 1]);

        DB::enableQueryLog();
        Livewire::test(AssetStatusChart::class); // renders: getData() + getOptions()

        $statusQueries = array_filter(
            DB::getQueryLog(),
            fn (array $query): bool => str_contains($query['query'], 'group by') && str_contains($query['query'], 'status'),
        );

        $this->assertCount(1, $statusQueries);
    }

    public function test_bars_are_thin_and_fully_rounded(): void
    {
        $this->seedStatuses([AssetStatus::BALANCE->value => 1]);

        $dataset = $this->callWidget('getData')['datasets'][0];

        $this->assertLessThanOrEqual(14, $dataset['barThickness']);
        $this->assertGreaterThanOrEqual(6, $dataset['borderRadius']);
        $this->assertFalse($dataset['borderSkipped']);
        $this->assertFalse($dataset['grouped'], 'pill and track must overlap');
        $this->assertGreaterThanOrEqual(8, $dataset['minBarLength'], 'a tiny group must stay visible');
    }

    public function test_every_bar_has_a_light_track_as_long_as_the_biggest_group(): void
    {
        $this->seedStatuses([
            AssetStatus::BALANCE->value => 7,
            AssetStatus::REPAIR->value => 2,
            AssetStatus::LOST->value => 1,
        ]);

        $datasets = $this->callWidget('getData')['datasets'];

        $this->assertCount(2, $datasets);
        $this->assertSame([7, 7, 7], $datasets[1]['data']);
        $this->assertSame(AssetStatusChart::TRACK_COLOR, $datasets[1]['backgroundColor']);
        $this->assertGreaterThan($datasets[0]['order'], $datasets[1]['order'], 'the track is drawn behind the pill');
        $this->assertFalse($datasets[1]['grouped']);
    }

    public function test_description_is_one_short_line_like_the_neighbouring_chart(): void
    {
        $statusDescription = (new ReflectionClass(AssetStatusChart::class))->getDefaultProperties()['description'];

        $this->assertNotNull($statusDescription);
        $this->assertLessThanOrEqual(40, mb_strlen($statusDescription));
    }

    public function test_is_a_clean_horizontal_chart_whose_scale_ends_at_the_biggest_group(): void
    {
        $this->seedStatuses([AssetStatus::BALANCE->value => 7, AssetStatus::REPAIR->value => 3]);

        $this->assertSame('bar', $this->callWidget('getType'));

        $options = $this->callWidget('getOptions');

        $this->assertSame('y', $options['indexAxis']);
        $this->assertFalse($options['plugins']['legend']['display']);
        $this->assertFalse($options['plugins']['tooltip']['enabled']);
        $this->assertFalse($options['scales']['x']['display']);
        $this->assertSame(0, $options['scales']['x']['min']);
        $this->assertSame(7, $options['scales']['x']['max']);
        $this->assertFalse($options['scales']['y']['grid']['display']);
    }

    public function test_scale_has_no_max_when_there_are_no_assets(): void
    {
        $options = $this->callWidget('getOptions');

        $this->assertArrayNotHasKey('max', $options['scales']['x']);
    }

    public function test_long_labels_are_split_into_two_lines_and_short_ones_are_left_alone(): void
    {
        $this->assertSame('на балансі — 9', AssetStatusChart::wrapLabel('на балансі — 9'));
        $this->assertSame('поступає на баланс — 2', AssetStatusChart::wrapLabel('поступає на баланс — 2'));
        $this->assertSame(['проводиться', 'передача — 2'], AssetStatusChart::wrapLabel('проводиться передача — 2'));
        $this->assertSame(['проводиться', 'списання — 12'], AssetStatusChart::wrapLabel('проводиться списання — 12'));
    }

    public function test_a_single_long_word_is_not_wrapped(): void
    {
        $this->assertSame(str_repeat('Ж', 40), AssetStatusChart::wrapLabel(str_repeat('Ж', 40)));
    }

    public function test_chart_labels_wrap_the_two_long_statuses(): void
    {
        $this->seedStatuses([
            AssetStatus::TRANSFERRING->value => 3,
            AssetStatus::BALANCE->value => 9,
        ]);

        $labels = $this->callWidget('getData')['labels'];

        $this->assertSame('на балансі — 9', $labels[0]);
        $this->assertSame(['проводиться', 'передача — 3'], $labels[1]);
    }
}
