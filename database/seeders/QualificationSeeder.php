<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Qualification;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class QualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resourceTypes = ResourceType::query()->pluck('id', 'name');
        $personTypeId = $resourceTypes->get('Person');
        $equipmentTypeId = $resourceTypes->get('Equipment');

        $qualifications = [
            ['name' => 'Project Management', 'description' => 'Planning, estimating, and delivery coordination.', 'resource_type_id' => $personTypeId],
            ['name' => 'Frontend Development', 'description' => 'UI implementation and accessibility.', 'resource_type_id' => $personTypeId],
            ['name' => 'Backend Development', 'description' => 'API and data layer implementation.', 'resource_type_id' => $personTypeId],
            ['name' => 'UX Research', 'description' => 'User interviews and usability testing.', 'resource_type_id' => $personTypeId],
            ['name' => 'Workshop Facilitation', 'description' => 'Leading discovery and alignment sessions.', 'resource_type_id' => $personTypeId],
            ['name' => 'Forklift Certified', 'description' => 'Certified to operate warehouse forklifts.', 'resource_type_id' => $equipmentTypeId],
            ['name' => 'Audio Setup', 'description' => 'Configure audio and AV equipment.', 'resource_type_id' => $equipmentTypeId],
            ['name' => 'Safety Training', 'description' => 'General safety and compliance training.', 'resource_type_id' => null],
        ];

        foreach ($qualifications as $qualification) {
            Qualification::query()->create($qualification);
        }
    }
}
