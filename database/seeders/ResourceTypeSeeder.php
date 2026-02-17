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
            ['name' => 'Person', 'description' => 'Einzelpersonen als Ressourcen.'],
            ['name' => 'Team', 'description' => 'Querschnittsteam-Kapazität.'],
            ['name' => 'Room', 'description' => 'Buchbare Räume und Flächen.'],
            ['name' => 'Equipment', 'description' => 'Gemeinsam genutzte Werkzeuge und Geräte.'],
        ];

        foreach ($resourceTypes as $resourceType) {
            ResourceType::query()->create($resourceType);
        }
    }
}
