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

class AssetComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function workstation(): Asset
    {
        $type = AssetType::firstOrCreate(['name' => AssetType::WORKSTATION_NAME]);

        return Asset::factory()->create(['type_id' => $type->getKey()]);
    }

    public function test_admin_creates_a_component_with_all_fields_from_the_asset_card(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $asset = $this->workstation();
        $location = Location::factory()->create();
        $holder = Employee::factory()->create();

        Livewire::test(ComponentsRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
            ->callAction(TestAction::make('create')->table(), [
                'kind' => ComponentKind::MONITOR->value,
                'serial_number' => '12275338B2037',
                'description' => 'IIYAMA G2745HSU-B1',
                'location_id' => $location->getKey(),
                'holder_id' => $holder->getKey(),
                'is_working' => false,
            ])
            ->assertHasNoFormErrors();

        $component = AssetComponent::sole();
        $this->assertSame($asset->getKey(), $component->asset_id);
        $this->assertSame(ComponentKind::MONITOR, $component->kind);
        $this->assertSame('12275338B2037', $component->serial_number);
        $this->assertSame('IIYAMA G2745HSU-B1', $component->description);
        $this->assertSame($location->getKey(), $component->location_id);
        $this->assertSame($holder->getKey(), $component->holder_id);
        $this->assertFalse($component->is_working);
    }

    public function test_a_workstation_can_have_three_monitors_with_different_serial_numbers(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $asset = $this->workstation();

        foreach (['SN-1', 'SN-2', 'SN-3'] as $serial) {
            Livewire::test(ComponentsRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
                ->callAction(TestAction::make('create')->table(), ['kind' => ComponentKind::MONITOR->value, 'serial_number' => $serial])
                ->assertHasNoFormErrors();
        }

        $this->assertEqualsCanonicalizing(
            ['SN-1', 'SN-2', 'SN-3'],
            $asset->components()->where('kind', ComponentKind::MONITOR)->pluck('serial_number')->all(),
        );

        Livewire::test(ComponentsRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
            ->assertCanSeeTableRecords($asset->components);
    }

    public function test_only_the_kind_is_required(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $asset = $this->workstation();

        Livewire::test(ComponentsRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
            ->callAction(TestAction::make('create')->table(), ['kind' => null])
            ->assertHasFormErrors(['kind' => 'required']);

        Livewire::test(ComponentsRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
            ->callAction(TestAction::make('create')->table(), ['kind' => ComponentKind::PRINTER->value])
            ->assertHasNoFormErrors();
    }

    public function test_the_tab_exists_only_for_the_workstation_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->assertTrue(ComponentsRelationManager::canViewForRecord($this->workstation(), ViewAsset::class));
        $this->assertFalse(ComponentsRelationManager::canViewForRecord(Asset::factory()->create(), ViewAsset::class));
        $this->assertFalse(ComponentsRelationManager::canViewForRecord(
            Asset::factory()->state(['type_id' => null])->create(),
            ViewAsset::class,
        ));
    }

    public function test_the_asset_page_shows_the_components_tab_only_for_a_workstation(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(ViewAsset::getUrl(['record' => $this->workstation()]))->assertSee('Додайте монітори, системний блок');
        $this->get(ViewAsset::getUrl(['record' => Asset::factory()->create()]))->assertDontSee('Додайте монітори, системний блок');
    }
}
