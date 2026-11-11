<?php

namespace Database\Factories;

use App\Enums\IncomingType;
use App\Models\Asset;
use App\Models\AssetIncoming;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetIncoming>
 */
class AssetIncomingFactory extends Factory
{
    protected $model = AssetIncoming::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'incoming_type' => IncomingType::NEW,
            'source' => 'Склад',
            'document_number' => fake()->numerify('НК-####'),
            'received_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'completed_at' => null,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['completed_at' => $attributes['received_at']]);
    }

    public function fromDepartments(): static
    {
        return $this->state(['incoming_type' => IncomingType::ALREADY_IN_USE]);
    }
}
