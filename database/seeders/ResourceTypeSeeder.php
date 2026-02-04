<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resourceTypes = [
            ['name' => 'Person', 'description' => 'Individual people resources.'],
            ['name' => 'Team', 'description' => 'Cross-functional group capacity.'],
            ['name' => 'Room', 'description' => 'Bookable spaces and rooms.'],
            ['name' => 'Equipment', 'description' => 'Shared tools and assets.'],
        ];

        foreach ($resourceTypes as $resourceType) {
            ResourceType::query()->create($resourceType);
        }
    }
}
