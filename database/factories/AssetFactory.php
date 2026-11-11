<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'inventory_number' => fake()->unique()->numerify('10#######'),
            'serial_number' => strtoupper(fake()->unique()->bothify('SN-????-#####')),
            'notes' => null,
            'year' => fake()->numberBetween(2015, 2025),
            'location_id' => Location::factory(),
            'type_id' => AssetType::factory(),
            'custodian_id' => Employee::factory(),
            'holder_id' => null,
            'status' => AssetStatus::BALANCE,
        ];
    }

    public function status(AssetStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function custodian(Employee $employee): static
    {
        return $this->state(['custodian_id' => $employee->getKey()]);
    }

    public function heldBy(Employee $employee): static
    {
        return $this->state(['holder_id' => $employee->getKey()]);
    }
}
