<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Мінімальні дані для Playwright e2e-тестів. Запускається лише на окремій e2e-базі.
 */
class E2ESeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@e2e.test';

    public const ADMIN_PASSWORD = 'password';

    public function run(): void
    {
        $employee = Employee::create(['full_name' => 'E2E Адміністратор', 'position' => 'МВО']);

        User::create([
            'name' => 'E2E Admin',
            'email' => self::ADMIN_EMAIL,
            'password' => self::ADMIN_PASSWORD,
            'role' => UserRole::ADMIN,
            'employee_id' => $employee->id,
        ]);

        $location = Location::create(['name' => 'E2E Склад']);
        $type = AssetType::create(['name' => 'E2E Ноутбук']);

        Asset::create([
            'name' => 'E2E Тестовий актив',
            'inventory_number' => '101000001',
            'serial_number' => 'E2E-SN-1',
            'year' => 2024,
            'location_id' => $location->id,
            'type_id' => $type->id,
            'custodian_id' => $employee->id,
            'holder_id' => $employee->id,
            'status' => AssetStatus::BALANCE,
        ]);
    }
}
