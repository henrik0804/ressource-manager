<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Qualification;
use App\Models\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Qualification>
 */
class QualificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(12),
            'resource_type_id' => fake()->boolean(70) ? ResourceType::factory() : null,
        ];
    }
}
