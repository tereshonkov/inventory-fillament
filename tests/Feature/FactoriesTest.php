<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AssetIncoming;
use App\Models\AssetTransfer;
use App\Models\AssetWriteOff;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{AssetStatus}> */
    public static function statuses(): array
    {
        $cases = [];

        foreach (AssetStatus::cases() as $status) {
            $cases[$status->value] = [$status];
        }

        return $cases;
    }

    #[DataProvider('statuses')]
    public function test_asset_factory_creates_asset_in_every_status(AssetStatus $status): void
    {
        $asset = Asset::factory()->status($status)->create();

        $this->assertSame($status, $asset->fresh()->status);
        $this->assertNotNull($asset->location);
        $this->assertNotNull($asset->type);
        $this->assertNotNull($asset->custodian);
    }

    public function test_asset_factory_supports_custodian_and_holder(): void
    {
        $custodian = Employee::factory()->create();
        $holder = Employee::factory()->create();

        $asset = Asset::factory()->custodian($custodian)->heldBy($holder)->create();

        $this->assertTrue($asset->custodian->is($custodian));
        $this->assertTrue($asset->holder->is($holder));
    }

    public function test_document_factories_create_documents_for_an_asset(): void
    {
        $asset = Asset::factory()->create();

        AssetIncoming::factory()->for($asset)->completed()->create();
        AssetTransfer::factory()->for($asset)->create();
        AssetWriteOff::factory()->for($asset)->create();

        $this->assertCount(1, $asset->incomings);
        $this->assertNotNull($asset->incomings->first()->completed_at);
        $this->assertCount(1, $asset->transfers);
        $this->assertNull($asset->transfers->first()->completed_at);
        $this->assertCount(1, $asset->writeOffs);
    }

    public function test_user_factory_role_states(): void
    {
        $employee = Employee::factory()->create();

        $this->assertSame(UserRole::ADMIN, User::factory()->admin()->create()->role);
        $this->assertSame(UserRole::VIEWER, User::factory()->viewer()->create()->role);

        $editor = User::factory()->editor($employee)->create();

        $this->assertSame(UserRole::EDITOR, $editor->role);
        $this->assertSame($employee->getKey(), $editor->employee_id);
    }
}
