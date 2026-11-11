<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Assets\Pages\ViewAsset;
use App\Filament\Resources\Assets\RelationManagers\IncomingsRelationManager;
use App\Filament\Resources\Assets\RelationManagers\TransfersRelationManager;
use App\Filament\Resources\Assets\RelationManagers\WriteOffsRelationManager;
use App\Models\Asset;
use App\Models\AssetIncoming;
use App\Models\AssetTransfer;
use App\Models\AssetWriteOff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssetHistoryTabsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    /** @return array<string, array{class-string, string}> */
    public static function managers(): array
    {
        return [
            'incomings' => [IncomingsRelationManager::class, 'Надходжень ще не було'],
            'transfers' => [TransfersRelationManager::class, 'Передач ще не було'],
            'write-offs' => [WriteOffsRelationManager::class, 'Списань ще не було'],
        ];
    }

    public function test_badges_show_the_number_of_records_and_are_hidden_when_there_are_none(): void
    {
        $asset = Asset::factory()->create();

        $this->assertNull(IncomingsRelationManager::getBadge($asset, ViewAsset::class));
        $this->assertNull(TransfersRelationManager::getBadge($asset, ViewAsset::class));
        $this->assertNull(WriteOffsRelationManager::getBadge($asset, ViewAsset::class));

        AssetIncoming::factory()->count(2)->for($asset)->create();
        AssetTransfer::factory()->for($asset)->create();
        AssetWriteOff::factory()->count(3)->for($asset)->create();

        $this->assertSame('2', IncomingsRelationManager::getBadge($asset, ViewAsset::class));
        $this->assertSame('1', TransfersRelationManager::getBadge($asset, ViewAsset::class));
        $this->assertSame('3', WriteOffsRelationManager::getBadge($asset, ViewAsset::class));
    }

    public function test_badges_count_only_records_of_the_given_asset(): void
    {
        $asset = Asset::factory()->create();
        AssetIncoming::factory()->for($asset)->create();
        AssetIncoming::factory()->count(5)->create(); // other assets

        $this->assertSame('1', IncomingsRelationManager::getBadge($asset, ViewAsset::class));
    }

    /** @param class-string $manager */
    #[DataProvider('managers')]
    public function test_empty_tab_shows_a_ukrainian_empty_state(string $manager, string $heading): void
    {
        $asset = Asset::factory()->create();

        Livewire::test($manager, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
            ->assertSee($heading)
            ->assertSee('Записи з’являться після оформлення')
            ->assertDontSeeHtml('aria-label="asset');
    }

    public function test_tab_with_records_lists_them_instead_of_the_empty_state(): void
    {
        $asset = Asset::factory()->create();
        $incomings = AssetIncoming::factory()->count(2)->for($asset)->create();

        Livewire::test(IncomingsRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
            ->assertCanSeeTableRecords($incomings)
            ->assertDontSee('Надходжень ще не було');
    }
}
