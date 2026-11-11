<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\User;
use Database\Seeders\E2ESeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class E2ESeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_users_with_expected_roles(): void
    {
        $this->seed(E2ESeeder::class);

        $admin = User::where('email', E2ESeeder::ADMIN_EMAIL)->firstOrFail();
        $editor = User::where('email', E2ESeeder::EDITOR_EMAIL)->firstOrFail();

        $this->assertSame(UserRole::ADMIN, $admin->role);
        $this->assertSame(UserRole::EDITOR, $editor->role);
        $this->assertNotNull($editor->employee_id);
        $this->assertSame(UserRole::VIEWER, User::where('email', E2ESeeder::VIEWER_EMAIL)->firstOrFail()->role);
    }

    public function test_seeder_creates_an_own_and_a_foreign_asset_for_every_status(): void
    {
        $this->seed(E2ESeeder::class);

        $editor = User::where('email', E2ESeeder::EDITOR_EMAIL)->firstOrFail();

        foreach (AssetStatus::cases() as $status) {
            $this->assertSame(
                1,
                Asset::where('status', $status)->where('custodian_id', $editor->employee_id)
                    ->where('name', 'like', 'E2E Свій%')->count(),
                "editor must own exactly one asset in status {$status->value}",
            );
            $this->assertSame(
                1,
                Asset::where('status', $status)->where('custodian_id', '!=', $editor->employee_id)
                    ->where('name', 'like', 'E2E Чужий%')->count(),
                "exactly one foreign asset in status {$status->value}",
            );
        }
    }

    public function test_seeder_attaches_documents_to_the_main_asset(): void
    {
        $this->seed(E2ESeeder::class);

        $main = Asset::where('name', E2ESeeder::MAIN_ASSET_NAME)->firstOrFail();

        $this->assertSame(2, $main->incomings()->count());
        $this->assertSame(1, $main->transfers()->count());
        $this->assertSame(1, $main->writeOffs()->count());
    }

    public function test_seeder_provides_dashboard_stat_tile_data(): void
    {
        $this->seed(E2ESeeder::class);

        $this->assertSame(2, Asset::whereHas('type', fn ($q) => $q->where('name', 'ПК'))
            ->whereHas('location', fn ($q) => $q->where('name', 'Склад-327'))
            ->where('status', AssetStatus::BALANCE)->count());
    }
}
