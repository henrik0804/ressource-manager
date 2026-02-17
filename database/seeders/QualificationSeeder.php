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
            ['name' => 'Projektmanagement', 'description' => 'Planung, Schätzung und Lieferkoordination.', 'resource_type_id' => $personTypeId],
            ['name' => 'Frontend-Entwicklung', 'description' => 'UI-Implementierung und Barrierefreiheit.', 'resource_type_id' => $personTypeId],
            ['name' => 'Backend-Entwicklung', 'description' => 'API- und Datenebenen-Implementierung.', 'resource_type_id' => $personTypeId],
            ['name' => 'UX-Forschung', 'description' => 'Benutzerinterviews und Usability-Tests.', 'resource_type_id' => $personTypeId],
            ['name' => 'Workshop-Moderation', 'description' => 'Leitung von Discoverys und Abstimmungssitzungen.', 'resource_type_id' => $personTypeId],
            ['name' => 'Gabelstapler-Führerschein', 'description' => 'Zertifiziert für den Betrieb von Lagergabelstaplern.', 'resource_type_id' => $equipmentTypeId],
            ['name' => 'Audio-Einrichtung', 'description' => 'Konfiguration von Audio- und AV-Geräten.', 'resource_type_id' => $equipmentTypeId],
            ['name' => 'Sicherheitsschulung', 'description' => 'Allgemeine Sicherheits- und Compliance-Schulung.', 'resource_type_id' => null],
        ];

        foreach ($qualifications as $qualification) {
            Qualification::query()->create($qualification);
        }
    }
}
