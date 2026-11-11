<?php

namespace Database\Factories;

use App\Enums\ComponentKind;
use App\Models\Asset;
use App\Models\AssetComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetComponent>
 */
class AssetComponentFactory extends Factory
{
    protected $model = AssetComponent::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'kind' => ComponentKind::MONITOR,
            'serial_number' => strtoupper(fake()->unique()->bothify('CMP-????-#####')),
            'description' => null,
            'location_id' => null,
            'holder_id' => null,
            'is_working' => true,
        ];
    }

    public function kind(ComponentKind $kind): static
    {
        return $this->state(['kind' => $kind]);
    }

    public function broken(): static
    {
        return $this->state(['is_working' => false]);
    }
}
