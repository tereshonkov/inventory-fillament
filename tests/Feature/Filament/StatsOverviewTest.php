<?php

namespace Tests\Feature\Filament;

use App\Enums\AssetStatus;
use App\Filament\Widgets\StatsOverview;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class StatsOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    /** @return array<int, Stat> */
    private function stats(): array
    {
        $widget = Livewire::test(StatsOverview::class)->instance();

        return (new ReflectionMethod($widget, 'getStats'))->invoke($widget);
    }

    /** @return array<string, string> label => value */
    private function tiles(): array
    {
        $tiles = [];

        foreach ($this->stats() as $stat) {
            $tiles[(string) $stat->getLabel()] = (string) $stat->getValue();
        }

        return $tiles;
    }

    private function makeAssets(int $count, string $type, string $location, AssetStatus $status = AssetStatus::BALANCE, ?Employee $custodian = null): void
    {
        $factory = Asset::factory()->count($count)->status($status);

        if ($custodian) {
            $factory = $factory->custodian($custodian);
        }

        $factory->create([
            'type_id' => AssetType::firstOrCreate(['name' => $type])->getKey(),
            'location_id' => Location::firstOrCreate(['name' => $location])->getKey(),
        ]);
    }

    public function test_counts_only_balance_assets_of_the_listed_types_on_the_two_warehouses(): void
    {
        $this->makeAssets(2, 'ПК', 'Склад-327');
        $this->makeAssets(1, 'ПК', 'Склад-915');
        $this->makeAssets(4, 'ПК', 'Каб. 101');                              // not a warehouse
        $this->makeAssets(5, 'ПК', 'Склад-327', AssetStatus::REPAIR);       // not on balance
        $this->makeAssets(1, 'Ноутбук', 'Склад-915');
        $this->makeAssets(7, 'Принтер', 'Склад-327');                        // type is not listed

        $this->assertSame([
            'ПК' => '3 шт',
            'Ноутбук' => '1 шт',
            'Робоча станція' => '0 шт',
            'Монітор' => '0 шт',
        ], $this->tiles());
    }

    public function test_tile_for_every_type_is_shown_even_when_the_type_is_missing_in_the_catalogue(): void
    {
        $this->assertCount(0, AssetType::all());

        $this->assertSame([
            'ПК' => '0 шт',
            'Ноутбук' => '0 шт',
            'Робоча станція' => '0 шт',
            'Монітор' => '0 шт',
        ], $this->tiles());
    }

    public function test_tiles_keep_a_fixed_order(): void
    {
        // Created in a different order than displayed.
        $this->makeAssets(1, 'Монітор', 'Склад-327');
        $this->makeAssets(1, 'ПК', 'Склад-327');

        $this->assertSame(['ПК', 'Ноутбук', 'Робоча станція', 'Монітор'], array_keys($this->tiles()));
    }

    public function test_numbers_are_global_and_not_scoped_by_role(): void
    {
        $this->makeAssets(3, 'ПК', 'Склад-327');                             // custodians of other employees

        $employee = Employee::factory()->create();
        $this->actingAs(User::factory()->editor($employee)->create());

        $this->assertSame('3 шт', $this->tiles()['ПК']);
    }

    public function test_stats_are_loaded_with_a_single_query(): void
    {
        $this->makeAssets(2, 'ПК', 'Склад-327');
        $widget = Livewire::test(StatsOverview::class)->instance();
        $method = new ReflectionMethod($widget, 'getStats');

        DB::enableQueryLog();
        $method->invoke($widget);

        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_widget_explains_what_is_counted(): void
    {
        Livewire::test(StatsOverview::class)
            ->assertSee('Наявність на складах')
            ->assertSee('на балансі')
            ->assertSee('Склад-327')
            ->assertSee('Склад-915');
    }
}
