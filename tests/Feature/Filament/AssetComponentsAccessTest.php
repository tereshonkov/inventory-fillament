<?php

namespace Tests\Feature\Filament;

use App\Enums\ComponentKind;
use App\Filament\Resources\AssetComponents\Pages\ListAssetComponents;
use App\Filament\Resources\Assets\Pages\ViewAsset;
use App\Filament\Resources\Assets\RelationManagers\ComponentsRelationManager;
use App\Models\Asset;
use App\Models\AssetComponent;
use App\Models\AssetType;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\ExpectationFailedException;
use Tests\TestCase;

class AssetComponentsAccessTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private Asset $ownStation;

    private Asset $foreignStation;

    private AssetComponent $own;

    private AssetComponent $foreign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $type = AssetType::firstOrCreate(['name' => AssetType::WORKSTATION_NAME]);
        $this->employee = Employee::factory()->create();
        $this->ownStation = Asset::factory()->custodian($this->employee)->create(['type_id' => $type->getKey()]);
        $this->foreignStation = Asset::factory()->create(['type_id' => $type->getKey()]);
        $this->own = AssetComponent::factory()->for($this->ownStation)->create(['description' => 'своя']);
        $this->foreign = AssetComponent::factory()->for($this->foreignStation)->create(['description' => 'чужа']);
    }

    private function manager(Asset $station)
    {
        return Livewire::test(ComponentsRelationManager::class, ['ownerRecord' => $station, 'pageClass' => ViewAsset::class]);
    }

    /** An action the user may not run is not available at all: the helper fails on a hidden action. */
    private function assertActionRefused(Asset $station, TestAction $action, array $data = []): void
    {
        try {
            $this->manager($station)->callAction($action, $data);
        } catch (ExpectationFailedException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('The action was available although it must be refused.');
    }

    public function test_viewer_reads_everything_but_cannot_create_edit_or_delete(): void
    {
        $this->actingAs(User::factory()->viewer()->create());

        $viewer = auth()->user();
        $this->assertTrue($viewer->can('viewAny', AssetComponent::class));
        $this->assertTrue($viewer->can('view', $this->own));
        $this->assertFalse($viewer->can('create', AssetComponent::class));
        $this->assertFalse($viewer->can('update', $this->own));
        $this->assertFalse($viewer->can('delete', $this->own));

        $this->manager($this->ownStation)->assertCanSeeTableRecords([$this->own]);

        // The actions are not only hidden: the test helper refuses to run them (they are not visible/authorized).
        $this->assertActionRefused($this->ownStation, TestAction::make('create')->table(), ['kind' => ComponentKind::MONITOR->value]);
        $this->assertActionRefused($this->ownStation, TestAction::make('edit')->table($this->own), ['description' => 'зламано']);
        $this->assertActionRefused($this->ownStation, TestAction::make('delete')->table($this->own));

        $this->assertSame(1, $this->ownStation->components()->count());
        $this->assertSame('своя', $this->own->fresh()->description);

        Livewire::test(ListAssetComponents::class)
            ->assertCanSeeTableRecords([$this->own, $this->foreign]);
    }

    public function test_editor_manages_components_of_his_own_station(): void
    {
        $this->actingAs(User::factory()->editor($this->employee)->create());

        $this->manager($this->ownStation)
            ->callAction(TestAction::make('create')->table(), ['kind' => ComponentKind::PRINTER->value, 'serial_number' => 'ED-1'])
            ->assertHasNoFormErrors()
            ->callAction(TestAction::make('edit')->table($this->own), ['description' => 'оновлена'])
            ->assertHasNoFormErrors();

        $this->assertTrue($this->ownStation->components()->where('serial_number', 'ED-1')->exists());
        $this->assertSame('оновлена', $this->own->fresh()->description);

        $this->manager($this->ownStation)->callAction(TestAction::make('delete')->table($this->own));
        $this->assertModelMissing($this->own);
    }

    public function test_editor_cannot_touch_components_of_a_foreign_station(): void
    {
        $editor = User::factory()->editor($this->employee)->create();
        $this->actingAs($editor);

        $this->assertFalse($editor->can('view', $this->foreign));
        $this->assertFalse($editor->can('update', $this->foreign));
        $this->assertFalse($editor->can('delete', $this->foreign));

        $this->assertActionRefused($this->foreignStation, TestAction::make('edit')->table($this->foreign), ['description' => 'зламано']);
        $this->assertActionRefused($this->foreignStation, TestAction::make('delete')->table($this->foreign));

        $this->assertSame('чужа', $this->foreign->fresh()->description);

        $this->get(ViewAsset::getUrl(['record' => $this->foreignStation]))->assertNotFound();
    }

    public function test_editor_sees_only_his_stations_components_in_the_general_list(): void
    {
        $this->actingAs(User::factory()->editor($this->employee)->create());

        Livewire::test(ListAssetComponents::class)
            ->assertCanSeeTableRecords([$this->own])
            ->assertCanNotSeeTableRecords([$this->foreign]);
    }

    public function test_deleting_a_station_removes_its_components_and_leaves_others(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->ownStation->delete();

        $this->assertModelMissing($this->own);
        $this->assertModelExists($this->foreign);
        $this->assertSame(0, AssetComponent::whereDoesntHave('asset')->count());
    }

    public function test_changing_the_category_keeps_components_linked_to_their_station(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->ownStation->update(['type_id' => AssetType::factory()->create()->getKey()]);

        $component = $this->own->fresh();
        $this->assertSame($this->ownStation->getKey(), $component->asset_id);
        $this->assertFalse($this->ownStation->fresh()->isWorkstation());
        // The unit is still reachable in the general list, with its station.
        Livewire::test(ListAssetComponents::class)->assertCanSeeTableRecords([$component]);
    }
}
