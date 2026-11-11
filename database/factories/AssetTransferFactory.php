<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetTransfer>
 */
class AssetTransferFactory extends Factory
{
    protected $model = AssetTransfer::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'department_id' => Department::factory(),
            'document_number' => fake()->numerify('АП-####'),
            'transferred_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'completed_at' => null,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['completed_at' => $attributes['transferred_at']]);
    }
}
