<?php

namespace Tests\Feature\Filament;

use App\Enums\ComponentKind;
use App\Filament\Resources\Assets\Pages\ViewAsset;
use App\Filament\Resources\Assets\RelationManagers\ComponentsRelationManager;
use App\Models\Asset;
use App\Models\AssetComponent;
use App\Models\AssetType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetCompositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function workstation(array $attributes = []): Asset
    {
        $type = AssetType::firstOrCreate(['name' => AssetType::WORKSTATION_NAME]);

        return Asset::factory()->create(['type_id' => $type->getKey()] + $attributes);
    }

    private function page(Asset $asset): string
    {
        return $this->get(ViewAsset::getUrl(['record' => $asset]))->assertOk()->getContent();
    }

    public function test_composition_is_grouped_by_kind_in_a_fixed_order_and_skips_empty_kinds(): void
    {
        $asset = $this->workstation();
        // Created in a different order than displayed; no printer and no mouse.
        AssetComponent::factory()->for($asset)->kind(ComponentKind::UPS)->create();
        AssetComponent::factory()->for($asset)->kind(ComponentKind::KEYBOARD)->create();
        AssetComponent::factory()->count(3)->for($asset)->kind(ComponentKind::MONITOR)->create();
        AssetComponent::factory()->for($asset)->kind(ComponentKind::PC)->create();

        $groups = $asset->compositionByKind();

        $this->assertSame(['monitor', 'pc', 'keyboard', 'ups'], $groups->keys()->all());
        $this->assertCount(3, $groups['monitor']);

        $html = $this->page($asset);
        $this->assertStringContainsString('data-kind="monitor"', $html);
        $this->assertStringNotContainsString('data-kind="printer"', $html);
        $this->assertStringNotContainsString('data-kind="mouse"', $html);
    }

    public function test_a_station_without_components_opens_and_says_so(): void
    {
        $this->get(ViewAsset::getUrl(['record' => $this->workstation()]))
            ->assertOk()
            ->assertSee('Склад станції')
            ->assertSee('Комплектуючих ще не додано.');
    }

    public function test_unit_data_is_shown_with_serial_holder_location_description_and_working_state(): void
    {
        $asset = $this->workstation();
        AssetComponent::factory()->for($asset)->kind(ComponentKind::MONITOR)->create([
            'serial_number' => '12275338B2037',
            'description' => 'IIYAMA G2745HSU-B1',
            'location_id' => Location::factory()->create(['name' => 'Склад-915'])->getKey(),
            'holder_id' => Employee::factory()->create(['full_name' => 'Петренко Петро'])->getKey(),
        ]);
        AssetComponent::factory()->for($asset)->kind(ComponentKind::MOUSE)->broken()->create();

        $this->get(ViewAsset::getUrl(['record' => $asset]))
            ->assertSee('12275338B2037')
            ->assertSee('IIYAMA G2745HSU-B1')
            ->assertSee('Склад-915')
            ->assertSee('Петренко Петро')
            ->assertSee('Справна')
            ->assertSee('Несправна');
    }

    public function test_empty_optional_fields_fall_back_to_the_station_data(): void
    {
        $holder = Employee::factory()->create(['full_name' => 'Користувач Станції']);
        $asset = $this->workstation([
            'location_id' => Location::factory()->create(['name' => 'Кабінет станції'])->getKey(),
            'holder_id' => $holder->getKey(),
        ]);
        $component = AssetComponent::factory()->for($asset)->kind(ComponentKind::PRINTER)->create([
            'serial_number' => null, 'description' => null, 'location_id' => null, 'holder_id' => null,
        ]);

        $this->assertSame('Кабінет станції', $component->fresh()->effectiveLocationName());
        $this->assertSame('Користувач Станції', $component->fresh()->effectiveHolderName());

        $this->get(ViewAsset::getUrl(['record' => $asset]))
            ->assertOk()
            ->assertSee('Кабінет станції')
            ->assertSee('Користувач Станції')
            ->assertSee('як у станції');
    }

    public function test_own_location_wins_over_the_station_location(): void
    {
        $asset = $this->workstation(['location_id' => Location::factory()->create(['name' => 'Кабінет станції'])->getKey()]);
        $component = AssetComponent::factory()->for($asset)->create([
            'location_id' => Location::factory()->create(['name' => 'Склад-327'])->getKey(),
        ]);

        $this->assertSame('Склад-327', $component->effectiveLocationName());
        $this->assertFalse($component->usesStationLocation());
    }

    public function test_the_composition_block_exists_only_for_a_workstation(): void
    {
        $this->get(ViewAsset::getUrl(['record' => Asset::factory()->create()]))
            ->assertOk()
            ->assertDontSee('Склад станції');
    }

    public function test_editing_one_monitor_does_not_touch_the_others_and_notes_stay_with_their_unit(): void
    {
        $asset = $this->workstation();
        $keeper = Employee::factory()->create();
        $warehouse = Location::factory()->create(['name' => 'Склад-327']);
        $first = AssetComponent::factory()->for($asset)->kind(ComponentKind::MONITOR)->create([
            'holder_id' => $keeper->getKey(), 'description' => 'нотатка 1',
        ]);
        $second = AssetComponent::factory()->for($asset)->kind(ComponentKind::MONITOR)->create([
            'holder_id' => $keeper->getKey(), 'description' => 'нотатка 2',
        ]);
        $foreign = AssetComponent::factory()->for(Asset::factory()->create())->create(['description' => 'чужа нотатка']);

        Livewire::test(ComponentsRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
            ->callAction(TestAction::make('edit')->table($second), [
                'location_id' => $warehouse->getKey(),
                'holder_id' => null,
                'is_working' => false,
            ])
            ->assertHasNoFormErrors();

        $second->refresh();
        $this->assertSame($warehouse->getKey(), $second->location_id);
        $this->assertNull($second->holder_id);
        $this->assertFalse($second->is_working);
        $this->assertSame('нотатка 2', $second->description);

        $first->refresh();
        $this->assertSame($keeper->getKey(), $first->holder_id);
        $this->assertNull($first->location_id);
        $this->assertTrue($first->is_working);
        $this->assertSame('нотатка 1', $first->description);
        $this->assertSame('чужа нотатка', $foreign->fresh()->description);

        $html = $this->page($asset);
        $this->assertStringContainsString('нотатка 1', $html);
        $this->assertStringNotContainsString('чужа нотатка', $html);
    }
}
