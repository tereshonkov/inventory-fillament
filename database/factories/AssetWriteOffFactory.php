<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetWriteOff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetWriteOff>
 */
class AssetWriteOffFactory extends Factory
{
    protected $model = AssetWriteOff::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'reason' => 'Фізичний знос',
            'document_number' => fake()->numerify('АС-####'),
            'written_off_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'completed_at' => null,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['completed_at' => $attributes['written_off_at']]);
    }
}
