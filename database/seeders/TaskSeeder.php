<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EffortUnit;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = [
            [
                'title' => 'Büroflügel Renovierung',
                'description' => 'Komplette Renovierung des östlichen Büroflügels inklusive Möbelentsorgung, Streichen, Teppichverlegung und IT-Infrastruktur. Koordination zwischen Facility Management, IT und externen Handwerkern erforderlich.',
                'effort_value' => 12,
                'effort_unit' => EffortUnit::Days,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::InProgress,
                'start_offset' => -3,
                'duration_days' => 5,
            ],
            [
                'title' => 'Produktversion 3.0 Start sprint',
                'description' => 'Abschließender Entwicklungssprint für das Produkt-Release Version 3.0 mit UI-Verbesserungen, API-Optimierung, Performance-Tests und Koordination mit dem Marketing.',
                'effort_value' => 120,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Urgent,
                'status' => TaskStatus::Planned,
                'start_offset' => 1,
                'duration_days' => 8,
            ],
            [
                'title' => 'Jährliche Sicherheitsprüfung',
                'description' => 'Werksweite Sicherheits-Compliance-Prüfung inklusive Gerätezertifizierungen, Überprüfung der Notfallverfahren, Brandschutzinspektion und Dokumentationsaktualisierung.',
                'effort_value' => 18,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Planned,
                'start_offset' => 5,
                'duration_days' => 3,
            ],
            [
                'title' => 'Kunden-Workshop — Meridian Corp',
                'description' => 'Zweitägiger moderierter Workshop mit den Stakeholdern von Meridian Corp zur Definition des Projektumfangs, Erfolgskriterien und Ressourcenanforderungen für ihre Plattform-Migration.',
                'effort_value' => 2,
                'effort_unit' => EffortUnit::Days,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::Planned,
                'start_offset' => 3,
                'duration_days' => 2,
            ],
            [
                'title' => 'Lagerbestands-Ausgleich',
                'description' => 'Vollständige Inventur mit Barcode-Verifikation, Schadensbewertung und Systemabgleich mit den ERP-Daten. Inklusive physischer Neueinräumung von falsch eingeordnetem Bestand.',
                'effort_value' => 20,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::InProgress,
                'start_offset' => -2,
                'duration_days' => 4,
            ],
            [
                'title' => 'Einarbeitung neue Mitarbeiter — Q1 Kohorte',
                'description' => 'Strukturierte Einarbeitungswoche für sechs neue Mitarbeiter inklusive Orientierungstagen, Sicherheitsschulungen, Systemzugangs-Einrichtung und Abteilungs-Rotationen.',
                'effort_value' => 3,
                'effort_unit' => EffortUnit::Days,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::Planned,
                'start_offset' => 7,
                'duration_days' => 5,
            ],
            [
                'title' => 'Messestand Fertigung',
                'description' => 'Design, Prototyp und Montage des Messestands für die TechExpo 2026 inklusive 3D-gedruckter Display-Elemente, gebrandeter Platten und AV-Technik-Integration.',
                'effort_value' => 5,
                'effort_unit' => EffortUnit::Days,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Planned,
                'start_offset' => 4,
                'duration_days' => 6,
            ],
            [
                'title' => 'IT-Infrastruktur Migration',
                'description' => 'Migration der lokalen Server in ein Hybrid-Cloud-Setup inklusive Datensicherung, Netzwerk-Konfiguration, Anwendungstests, DNS-Umstellung und Rollback-Planung.',
                'effort_value' => 80,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Urgent,
                'status' => TaskStatus::Planned,
                'start_offset' => 10,
                'duration_days' => 7,
            ],
            [
                'title' => 'Quartalsreview Vorbereitung',
                'description' => 'Zusammenstellung der Abteilungs-KPIs, Finanzübersichten und Ressourcenauslastungsberichte für die Q1 Geschäftsführungs-Präsentation. Inklusive Folien-Erstellung und Probe-Durchlauf.',
                'effort_value' => 8,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Done,
                'start_offset' => -10,
                'duration_days' => 2,
            ],
            [
                'title' => 'Wartungsfenster Ausstattung',
                'description' => 'Geplante vorbeugende Wartung für alle Lager-Geräte inklusive Gabelstapler-Service, 3D-Drucker-Kalibrierung, Förderband-Inspektion und AV-System Firmware-Updates.',
                'effort_value' => 16,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::Planned,
                'start_offset' => 14,
                'duration_days' => 3,
            ],
            [
                'title' => 'Prozessoptimierung Team-übergreifend',
                'description' => 'Gemeinsame Prozessanalyse und Verbesserungsinitiative zwischen Entwicklungs- und Betriebsteams zur Reduzierung von Übergabeverzögerungen, Eliminierung doppelter Reviews und Standardisierung der Werkzeuge.',
                'effort_value' => 3,
                'effort_unit' => EffortUnit::Days,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Planned,
                'start_offset' => 2,
                'duration_days' => 4,
            ],
            [
                'title' => 'Notfallübung Koordination',
                'description' => 'Werksweite Notfall-Evakuierungsübung mit Echtzeit-Koordination, Nachbesprechung und Aktualisierung der Verfahrensdokumentation basierend auf beobachteten Lücken.',
                'effort_value' => 6,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Urgent,
                'status' => TaskStatus::Done,
                'start_offset' => -15,
                'duration_days' => 1,
            ],
        ];

        foreach ($tasks as $task) {
            $startsAt = now()->addDays($task['start_offset'])->setTime(9, 0);
            $endsAt = $startsAt->copy()->addDays($task['duration_days'] - 1)->setTime(17, 0);

            Task::query()->create([
                'title' => $task['title'],
                'description' => $task['description'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'effort_value' => $task['effort_value'],
                'effort_unit' => $task['effort_unit'],
                'priority' => $task['priority'],
                'status' => $task['status'],
            ]);
        }
    }
}
