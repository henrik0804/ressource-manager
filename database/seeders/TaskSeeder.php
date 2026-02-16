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
                'title' => 'Office Wing Renovation',
                'description' => 'Complete renovation of the east office wing including furniture removal, painting, new carpet installation, and IT infrastructure setup. Coordination required between facilities, IT, and external contractors.',
                'effort_value' => 12,
                'effort_unit' => EffortUnit::Days,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::InProgress,
                'start_offset' => -3,
                'duration_days' => 5,
            ],
            [
                'title' => 'v3.0 Product Launch Sprint',
                'description' => 'Final development sprint for the v3.0 product release covering frontend polish, API hardening, performance testing, and launch-day coordination with marketing.',
                'effort_value' => 120,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Urgent,
                'status' => TaskStatus::Planned,
                'start_offset' => 1,
                'duration_days' => 8,
            ],
            [
                'title' => 'Annual Safety Audit',
                'description' => 'Facility-wide safety compliance audit covering equipment certifications, emergency procedures review, fire safety inspection, and OSHA documentation updates.',
                'effort_value' => 18,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Planned,
                'start_offset' => 5,
                'duration_days' => 3,
            ],
            [
                'title' => 'Client Discovery Workshop — Meridian Corp',
                'description' => 'Two-day facilitated workshop with Meridian Corp stakeholders to define project scope, success criteria, and resource requirements for their platform migration.',
                'effort_value' => 2,
                'effort_unit' => EffortUnit::Days,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::Planned,
                'start_offset' => 3,
                'duration_days' => 2,
            ],
            [
                'title' => 'Warehouse Inventory Reconciliation',
                'description' => 'Full cycle count of warehouse inventory with barcode verification, damage assessment, and system reconciliation against ERP records. Includes physical re-shelving of misplaced stock.',
                'effort_value' => 20,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::InProgress,
                'start_offset' => -2,
                'duration_days' => 4,
            ],
            [
                'title' => 'New Hire Onboarding — Q1 Cohort',
                'description' => 'Structured onboarding week for six new hires including orientation sessions, safety training, system access setup, and department rotations across design and operations.',
                'effort_value' => 3,
                'effort_unit' => EffortUnit::Days,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::Planned,
                'start_offset' => 7,
                'duration_days' => 5,
            ],
            [
                'title' => 'Trade Show Booth Fabrication',
                'description' => 'Design, prototype, and assemble exhibition booth for TechExpo 2026 including 3D-printed display elements, branded panels, and AV equipment integration.',
                'effort_value' => 5,
                'effort_unit' => EffortUnit::Days,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Planned,
                'start_offset' => 4,
                'duration_days' => 6,
            ],
            [
                'title' => 'IT Infrastructure Migration',
                'description' => 'Migrate on-premise servers to hybrid cloud setup including data backup, network reconfiguration, application testing, DNS cutover, and rollback planning.',
                'effort_value' => 80,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Urgent,
                'status' => TaskStatus::Planned,
                'start_offset' => 10,
                'duration_days' => 7,
            ],
            [
                'title' => 'Quarterly Business Review Preparation',
                'description' => 'Compile department KPIs, financial summaries, and resource utilization reports for the Q1 executive review presentation. Includes slide deck creation and dry-run rehearsal.',
                'effort_value' => 8,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Done,
                'start_offset' => -10,
                'duration_days' => 2,
            ],
            [
                'title' => 'Equipment Maintenance Window',
                'description' => 'Scheduled preventive maintenance for all warehouse equipment including forklift servicing, 3D printer calibration, conveyor belt inspection, and AV system firmware updates.',
                'effort_value' => 16,
                'effort_unit' => EffortUnit::Hours,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::Planned,
                'start_offset' => 14,
                'duration_days' => 3,
            ],
            [
                'title' => 'Cross-Team Process Optimization',
                'description' => 'Collaborative process mapping and improvement initiative across design and operations teams to reduce handoff delays, eliminate duplicate reviews, and standardize tooling.',
                'effort_value' => 3,
                'effort_unit' => EffortUnit::Days,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Planned,
                'start_offset' => 2,
                'duration_days' => 4,
            ],
            [
                'title' => 'Emergency Drill Coordination',
                'description' => 'Facility-wide emergency evacuation drill with real-time coordination, post-drill debrief, and procedure documentation updates based on observed gaps.',
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
