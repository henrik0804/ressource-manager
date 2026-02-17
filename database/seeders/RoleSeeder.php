<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'description' => 'Voller Zugriff auf alle Einstellungen und Daten.'],
            ['name' => 'Planner', 'description' => 'Plant Arbeiten, weist Ressourcen zu und verwaltet Zeitpläne.'],
            ['name' => 'Manager', 'description' => 'Verantwortet Projektergebnisse und genehmigt Zeitpläne.'],
            ['name' => 'Contributor', 'description' => 'F zugewiesene Arbeitsaufgaben aus.'],
            ['name' => 'Viewer', 'description' => 'Nur-lesen-Zugriff auf Zeitpläne und Berichte.'],
        ];

        foreach ($roles as $role) {
            Role::query()->create($role);
        }
    }
}
