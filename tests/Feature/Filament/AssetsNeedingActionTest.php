<?php

namespace Tests\Feature\Filament;

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Widgets\AssetsNeedingAction;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetsNeedingActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function asset(AssetStatus $status, ?Employee $custodian = null, array $attributes = []): Asset
    {
        $factory = Asset::factory()->status($status);

        if ($custodian) {
            $factory = $factory->custodian($custodian);
        }

        return $factory->create($attributes);
    }

    /** @return array<int, Asset> one asset per status that needs action */
    private function needingAction(?Employee $custodian = null): array
    {
        return array_map(fn (AssetStatus $status): Asset => $this->asset($status, $custodian), AssetsNeedingAction::STATUSES);
    }

    /** @return array<int, Asset> one asset per status that does NOT need action */
    private function fine(?Employee $custodian = null): array
    {
        $fine = array_filter(AssetStatus::cases(), fn (AssetStatus $status): bool => ! in_array($status, AssetsNeedingAction::STATUSES, true));

        return array_map(fn (AssetStatus $status): Asset => $this->asset($status, $custodian), array_values($fine));
    }

    public function test_the_queue_covers_the_five_statuses_that_need_action(): void
    {
        $this->assertEqualsCanonicalizing([
            AssetStatus::NOT_PUT_IN_TO_OPERATION,
            AssetStatus::TRANSFERRING,
            AssetStatus::WRITING_OFF,
            AssetStatus::REPAIR,
            AssetStatus::LOST,
        ], AssetsNeedingAction::STATUSES);
    }

    public function test_admin_sees_assets_needing_action_of_everybody_and_not_the_others(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $queue = $this->needingAction();
        $others = $this->fine();

        Livewire::test(AssetsNeedingAction::class)
            ->assertCanSeeTableRecords($queue)
            ->assertCanNotSeeTableRecords($others);
    }

    public function test_editor_sees_only_the_assets_they_are_responsible_for(): void
    {
        $employee = Employee::factory()->create();
        $this->actingAs(User::factory()->editor($employee)->create());

        $own = $this->needingAction($employee);
        $foreign = $this->needingAction();
        $ownButFine = $this->fine($employee);

        Livewire::test(AssetsNeedingAction::class)
            ->assertCanSeeTableRecords($own)
            ->assertCanNotSeeTableRecords($foreign)
            ->assertCanNotSeeTableRecords($ownButFine);
    }

    public function test_viewer_sees_assets_needing_action_of_everybody(): void
    {
        $this->actingAs(User::factory()->viewer()->create());
        $queue = $this->needingAction();

        Livewire::test(AssetsNeedingAction::class)->assertCanSeeTableRecords($queue);
    }

    public function test_urgent_groups_come_first_transfer_and_write_off_then_lost_then_not_introduced_then_repair(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        // The most urgent statuses are deliberately the newest ones: the group wins over the waiting time.
        $repair = $this->asset(AssetStatus::REPAIR, null, ['updated_at' => now()->subDays(40)]);
        $notIntroduced = $this->asset(AssetStatus::NOT_PUT_IN_TO_OPERATION, null, ['updated_at' => now()->subDays(30)]);
        $lost = $this->asset(AssetStatus::LOST, null, ['updated_at' => now()->subDays(20)]);
        $writingOff = $this->asset(AssetStatus::WRITING_OFF, null, ['updated_at' => now()->subDays(2)]);
        $transferring = $this->asset(AssetStatus::TRANSFERRING, null, ['updated_at' => now()->subDay()]);

        Livewire::test(AssetsNeedingAction::class)
            ->assertCanSeeTableRecords([$writingOff, $transferring, $lost, $notIntroduced, $repair], inOrder: true);
    }

    public function test_within_the_most_urgent_group_transfer_and_write_off_are_ordered_by_waiting_time(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $newTransfer = $this->asset(AssetStatus::TRANSFERRING, null, ['updated_at' => now()->subDay()]);
        $oldWriteOff = $this->asset(AssetStatus::WRITING_OFF, null, ['updated_at' => now()->subDays(20)]);
        $middleTransfer = $this->asset(AssetStatus::TRANSFERRING, null, ['updated_at' => now()->subDays(5)]);

        Livewire::test(AssetsNeedingAction::class)
            ->assertCanSeeTableRecords([$oldWriteOff, $middleTransfer, $newTransfer], inOrder: true);
    }

    public function test_within_any_other_group_the_longest_waiting_comes_first(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $recent = $this->asset(AssetStatus::LOST, null, ['updated_at' => now()->subDay()]);
        $old = $this->asset(AssetStatus::LOST, null, ['updated_at' => now()->subDays(30)]);

        Livewire::test(AssetsNeedingAction::class)->assertCanSeeTableRecords([$old, $recent], inOrder: true);
    }

    public function test_the_status_list_follows_the_priority_order(): void
    {
        $this->assertSame(
            array_merge(...AssetsNeedingAction::PRIORITY_GROUPS),
            AssetsNeedingAction::STATUSES,
        );
    }

    public function test_it_shows_five_rows_per_page_by_default(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Asset::factory()->count(8)->status(AssetStatus::REPAIR)->create();

        $table = Livewire::test(AssetsNeedingAction::class)->instance();

        $this->assertSame(8, $table->getAllTableRecordsCount());
        $this->assertCount(5, $table->getTableRecords());
    }

    public function test_a_very_long_name_is_shown_as_at_most_four_words(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->asset(AssetStatus::REPAIR, null, ['name' => "Один Два Три Чотири П'ять Шість\n\nДругий абзац"]);

        Livewire::test(AssetsNeedingAction::class)
            ->assertSee('Один Два Три Чотири…')
            ->assertDontSee("П'ять");
    }

    public function test_a_row_links_to_the_asset_card(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $asset = $this->asset(AssetStatus::LOST);

        $table = Livewire::test(AssetsNeedingAction::class)->instance()->getTable();

        $this->assertSame(AssetResource::getUrl('view', ['record' => $asset]), $table->getRecordUrl($asset));
    }

    public function test_the_empty_queue_says_that_everything_is_fine(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->fine();

        Livewire::test(AssetsNeedingAction::class)
            ->assertSee('Усе в порядку')
            ->assertSee('Немає активів, які потребують уваги.');
    }

    public function test_the_dashboard_with_the_queue_opens_for_every_role(): void
    {
        $this->needingAction();

        foreach ([User::factory()->admin(), User::factory()->viewer(), User::factory()->editor()] as $factory) {
            $this->actingAs($factory->create())->get('/admin')->assertOk();
        }
    }
}
