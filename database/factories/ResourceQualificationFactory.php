<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceQualification>
 */
class ResourceQualificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resource_id' => Resource::factory(),
            'qualification_id' => Qualification::factory(),
            'level' => fake()->optional()->randomElement(QualificationLevel::cases()),
        ];
    }
}
