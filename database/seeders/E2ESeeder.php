<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\ComponentKind;
use App\Models\Asset;
use App\Models\AssetComponent;
use App\Models\AssetIncoming;
use App\Models\AssetTransfer;
use App\Models\AssetType;
use App\Models\AssetWriteOff;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data for Playwright e2e tests. Runs only against the dedicated e2e database.
 *
 * Contents:
 * - admin (admin@e2e.test) and editor (editor@e2e.test) with their employees;
 * - asset types and warehouses used by the dashboard stat tiles;
 * - one asset in every status, owned by the editor ("own") or by another employee ("foreign");
 * - documents (incoming / transfer / write-off) for the main test asset.
 */
class E2ESeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@e2e.test';

    public const EDITOR_EMAIL = 'editor@e2e.test';

    public const PASSWORD = 'password';

    public const MAIN_ASSET_NAME = 'E2E Тестовий актив';

    public const WORKSTATION_NAME = 'E2E Робоча станція';

    /** Created right after the main asset, so its id is 2 (used by e2e tests). */
    public const LONG_NAME_ASSET_ID = 2;

    public function run(): void
    {
        $adminEmployee = Employee::factory()->create(['full_name' => 'E2E Адміністратор', 'position' => 'МВО']);
        $editorEmployee = Employee::factory()->create(['full_name' => 'E2E Редактор', 'position' => 'МВО']);
        $otherEmployee = Employee::factory()->create(['full_name' => 'E2E Інший МВО', 'position' => 'МВО']);

        User::factory()->admin()->create([
            'name' => 'E2E Admin',
            'email' => self::ADMIN_EMAIL,
            'password' => self::PASSWORD,
            'employee_id' => $adminEmployee->getKey(),
        ]);

        User::factory()->editor($editorEmployee)->create([
            'name' => 'E2E Editor',
            'email' => self::EDITOR_EMAIL,
            'password' => self::PASSWORD,
        ]);

        $room = Location::create(['name' => 'E2E Склад']);
        $warehouse327 = Location::create(['name' => 'Склад-327']);
        $warehouse915 = Location::create(['name' => 'Склад-915']);

        $laptop = AssetType::create(['name' => 'Ноутбук']);
        $pc = AssetType::create(['name' => 'ПК']);
        $monitor = AssetType::create(['name' => 'Монітор']);
        $workstation = AssetType::create(['name' => AssetType::WORKSTATION_NAME]);

        $department = Department::create(['name' => 'E2E Відділ']);

        // Main asset: all view-card facts are filled in, documents are attached.
        $main = Asset::create([
            'name' => self::MAIN_ASSET_NAME,
            'inventory_number' => '101000001',
            'serial_number' => 'E2E-SN-1',
            'year' => 2024,
            'location_id' => $room->getKey(),
            'type_id' => $laptop->getKey(),
            'custodian_id' => $adminEmployee->getKey(),
            'holder_id' => $adminEmployee->getKey(),
            'status' => AssetStatus::BALANCE,
        ]);

        AssetIncoming::factory()->for($main)->completed()->create(['document_number' => 'E2E-НК-1']);
        AssetIncoming::factory()->for($main)->create(['document_number' => 'E2E-НК-2']);
        AssetTransfer::factory()->for($main)->for($department)->create(['document_number' => 'E2E-АП-1']);
        AssetWriteOff::factory()->for($main)->create(['document_number' => 'E2E-АС-1']);

        // Asset with a very long multi-paragraph name: headings must show it shortened.
        Asset::factory()->custodian($adminEmployee)->create([
            'name' => "Системний блок у зборі з монітором та периферією для відділу\n\nДругий абзац із додатковим описом комплектації\n\nТретій абзац з історією використання",
            'location_id' => $room->getKey(),
            'type_id' => $pc->getKey(),
        ]);

        // Stat-tile fixtures: balance assets on the two warehouses.
        Asset::factory()->count(2)->custodian($adminEmployee)->create([
            'type_id' => $pc->getKey(), 'location_id' => $warehouse327->getKey(), 'name' => 'E2E ПК',
        ]);
        Asset::factory()->count(3)->custodian($adminEmployee)->create([
            'type_id' => $monitor->getKey(), 'location_id' => $warehouse915->getKey(), 'name' => 'E2E Монітор',
        ]);

        // Workstation: its components are managed on the asset card (admin only in phase 1).
        $station = Asset::factory()->custodian($adminEmployee)->create([
            'name' => self::WORKSTATION_NAME,
            'inventory_number' => '101000777',
            'type_id' => $workstation->getKey(),
            'location_id' => $room->getKey(),
        ]);

        // Components on the warehouses feed the dashboard tiles: 1 monitor (broken counts too), 2 UPS.
        AssetComponent::factory()->for($station)->kind(ComponentKind::MONITOR)->broken()->create([
            'location_id' => $warehouse915->getKey(),
            'serial_number' => 'E2E-MON-SEED',
            'description' => 'Нотатка E2E монітора',
        ]);
        // No location and no holder of its own: the composition shows the station's data.
        AssetComponent::factory()->for($station)->kind(ComponentKind::KEYBOARD)->create(['serial_number' => 'E2E-KB-SEED']);
        AssetComponent::factory()->count(2)->for($station)->kind(ComponentKind::UPS)->create(['location_id' => $warehouse327->getKey()]);

        // One asset per status: an "own" one (editor is the custodian) and a "foreign" one.
        foreach (AssetStatus::cases() as $status) {
            Asset::factory()->status($status)->custodian($editorEmployee)->create([
                'name' => 'E2E Свій '.$status->value,
                'type_id' => $laptop->getKey(),
                'location_id' => $room->getKey(),
            ]);

            Asset::factory()->status($status)->custodian($otherEmployee)->create([
                'name' => 'E2E Чужий '.$status->value,
                'type_id' => $laptop->getKey(),
                'location_id' => $room->getKey(),
            ]);
        }
    }
}
