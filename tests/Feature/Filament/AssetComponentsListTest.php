<?php

namespace Tests\Feature\Filament;

use App\Enums\ComponentKind;
use App\Filament\Resources\AssetComponents\AssetComponentResource;
use App\Filament\Resources\AssetComponents\Pages\ListAssetComponents;
use App\Filament\Resources\Assets\AssetResource;
use App\Models\Asset;
use App\Models\AssetComponent;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetComponentsListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_the_list_contains_units_of_all_stations_including_those_on_a_warehouse(): void
    {
        $warehouse = Location::factory()->create(['name' => 'Склад-915']);
        $onWarehouse = AssetComponent::factory()->kind(ComponentKind::MONITOR)->create(['location_id' => $warehouse->getKey()]);
        $atStation = AssetComponent::factory()->kind(ComponentKind::MONITOR)->create();
        $ups = AssetComponent::factory()->kind(ComponentKind::UPS)->create();

        Livewire::test(ListAssetComponents::class)
            ->assertCanSeeTableRecords([$onWarehouse, $atStation, $ups])
            ->assertSee('Склад-915');
    }

    public function test_filter_by_kind_returns_only_that_kind_across_stations(): void
    {
        $monitors = AssetComponent::factory()->count(2)->kind(ComponentKind::MONITOR)->create();
        $printer = AssetComponent::factory()->kind(ComponentKind::PRINTER)->create();

        Livewire::test(ListAssetComponents::class)
            ->filterTable('kind', ComponentKind::MONITOR->value)
            ->assertCanSeeTableRecords($monitors)
            ->assertCanNotSeeTableRecords([$printer]);
    }

    public function test_search_by_serial_number_finds_exactly_that_unit(): void
    {
        $wanted = AssetComponent::factory()->create(['serial_number' => '12275338B2037']);
        $other = AssetComponent::factory()->create(['serial_number' => 'ZZZ-OTHER-1']);

        Livewire::test(ListAssetComponents::class)
            ->searchTable('12275338B2037')
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_inventory_number_of_a_unit_is_the_station_number_and_the_station_is_shown(): void
    {
        $station = Asset::factory()->create(['inventory_number' => '101000555', 'name' => 'Спеціалізована робоча станція Імпрешн']);
        AssetComponent::factory()->for($station)->create();

        Livewire::test(ListAssetComponents::class)
            ->assertSee('101000555')
            ->assertSee('Спеціалізована робоча станція Імпрешн');
    }

    public function test_a_row_leads_to_the_station_card_and_the_list_has_no_create_page(): void
    {
        $component = AssetComponent::factory()->create();

        $table = Livewire::test(ListAssetComponents::class)->instance()->getTable();

        $this->assertSame(
            AssetResource::getUrl('view', ['record' => $component->asset_id]),
            $table->getRecordUrl($component),
        );
        $this->assertFalse(AssetComponentResource::canCreate());
        $this->get(AssetComponentResource::getUrl())->assertOk();
    }
}
