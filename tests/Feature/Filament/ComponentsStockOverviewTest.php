<?php

namespace Tests\Feature\Filament;

use App\Enums\ComponentKind;
use App\Filament\Widgets\ComponentsStockOverview;
use App\Models\AssetComponent;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class ComponentsStockOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    /** @return array<string, string> label => value */
    private function tiles(): array
    {
        $widget = Livewire::test(ComponentsStockOverview::class)->instance();
        $tiles = [];

        foreach ((new ReflectionMethod($widget, 'getStats'))->invoke($widget) as $stat) {
            $tiles[(string) $stat->getLabel()] = (string) $stat->getValue();
        }

        return $tiles;
    }

    private function make(int $count, ComponentKind $kind, ?string $location, bool $working = true): void
    {
        AssetComponent::factory()->count($count)->kind($kind)->create([
            'location_id' => $location ? Location::firstOrCreate(['name' => $location])->getKey() : null,
            'is_working' => $working,
        ]);
    }

    public function test_counts_components_on_the_two_warehouses_regardless_of_working_state(): void
    {
        $this->make(1, ComponentKind::MONITOR, 'Склад-327');
        $this->make(2, ComponentKind::MONITOR, 'Склад-915', working: false);
        $this->make(4, ComponentKind::MONITOR, 'Каб. 101');   // not a warehouse
        $this->make(5, ComponentKind::MONITOR, null);          // no location
        $this->make(1, ComponentKind::PC, 'Склад-327');
        $this->make(1, ComponentKind::LAPTOP, 'Склад-915');
        $this->make(2, ComponentKind::UPS, 'Склад-915');
        $this->make(3, ComponentKind::PRINTER, 'Склад-327');
        $this->make(7, ComponentKind::MOUSE, 'Склад-327');     // kind has no tile

        $this->assertSame([
            'ПК (комплектуючі)' => '1 шт',
            'Монітор (комплектуючі)' => '3 шт',
            'Ноутбук (комплектуючі)' => '1 шт',
            'ДБЖ (комплектуючі)' => '2 шт',
            'Принтер (комплектуючі)' => '3 шт',
        ], $this->tiles());
    }

    public function test_all_five_tiles_are_shown_even_without_components(): void
    {
        $this->assertSame([
            'ПК (комплектуючі)' => '0 шт',
            'Монітор (комплектуючі)' => '0 шт',
            'Ноутбук (комплектуючі)' => '0 шт',
            'ДБЖ (комплектуючі)' => '0 шт',
            'Принтер (комплектуючі)' => '0 шт',
        ], $this->tiles());
    }

    public function test_stats_are_loaded_with_a_single_query(): void
    {
        $this->make(2, ComponentKind::MONITOR, 'Склад-327');
        $widget = Livewire::test(ComponentsStockOverview::class)->instance();
        $method = new ReflectionMethod($widget, 'getStats');

        DB::enableQueryLog();
        $method->invoke($widget);

        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_widget_explains_what_is_counted(): void
    {
        Livewire::test(ComponentsStockOverview::class)
            ->assertSee('Комплектуючі на складах')
            ->assertSee('Склад-327')
            ->assertSee('Склад-915');
    }
}
