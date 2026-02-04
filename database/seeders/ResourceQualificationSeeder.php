<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceQualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $personTypeId = ResourceType::query()->where('name', 'Person')->value('id');
        $resources = Resource::query()
            ->when($personTypeId, fn ($query) => $query->where('resource_type_id', $personTypeId))
            ->get();

        $qualifications = Qualification::query()
            ->when(
                $personTypeId,
                fn ($query) => $query->whereNull('resource_type_id')->orWhere('resource_type_id', $personTypeId)
            )
            ->get();

        if ($resources->isEmpty() || $qualifications->isEmpty()) {
            return;
        }

        $levels = QualificationLevel::cases();
        foreach ($resources as $resource) {
            $count = min(3, $qualifications->count());
            $selection = collect($qualifications->random(fake()->numberBetween(1, $count)));

            foreach ($selection as $qualification) {
                ResourceQualification::query()->create([
                    'resource_id' => $resource->id,
                    'qualification_id' => $qualification->id,
                    'level' => fake()->optional()->randomElement($levels),
                ]);
            }
        }
    }
}
