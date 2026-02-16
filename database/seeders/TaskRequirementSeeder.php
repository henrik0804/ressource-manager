<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Task;
use App\Models\TaskRequirement;
use Illuminate\Database\Seeder;

class TaskRequirementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Maps qualifications to tasks based on logical relevance rather than
     * random selection, so the test data tells a coherent story.
     */
    public function run(): void
    {
        $tasks = Task::query()->get()->keyBy('title');
        $qualifications = Qualification::query()->get()->keyBy('name');

        if ($tasks->isEmpty() || $qualifications->isEmpty()) {
            return;
        }

        /**
         * Requirement plans keyed by task title.
         *
         * Each entry specifies a qualification name and the minimum required level.
         *
         * @var array<string, list<array{qualification: string, level: QualificationLevel}>>
         */
        $plans = [
            'Office Wing Renovation' => [
                ['qualification' => 'Safety Training', 'level' => QualificationLevel::Intermediate],
                ['qualification' => 'Project Management', 'level' => QualificationLevel::Advanced],
            ],
            'v3.0 Product Launch Sprint' => [
                ['qualification' => 'Frontend Development', 'level' => QualificationLevel::Advanced],
                ['qualification' => 'Backend Development', 'level' => QualificationLevel::Advanced],
                ['qualification' => 'UX Research', 'level' => QualificationLevel::Intermediate],
            ],
            'Annual Safety Audit' => [
                ['qualification' => 'Safety Training', 'level' => QualificationLevel::Expert],
                ['qualification' => 'Forklift Certified', 'level' => QualificationLevel::Advanced],
            ],
            'Client Discovery Workshop — Meridian Corp' => [
                ['qualification' => 'Workshop Facilitation', 'level' => QualificationLevel::Advanced],
                ['qualification' => 'UX Research', 'level' => QualificationLevel::Intermediate],
            ],
            'Warehouse Inventory Reconciliation' => [
                ['qualification' => 'Safety Training', 'level' => QualificationLevel::Beginner],
                ['qualification' => 'Forklift Certified', 'level' => QualificationLevel::Intermediate],
            ],
            'New Hire Onboarding — Q1 Cohort' => [
                ['qualification' => 'Project Management', 'level' => QualificationLevel::Intermediate],
            ],
            'Trade Show Booth Fabrication' => [
                ['qualification' => 'Frontend Development', 'level' => QualificationLevel::Beginner],
                ['qualification' => 'UX Research', 'level' => QualificationLevel::Advanced],
            ],
            'IT Infrastructure Migration' => [
                ['qualification' => 'Backend Development', 'level' => QualificationLevel::Expert],
            ],
            'Quarterly Business Review Preparation' => [
                ['qualification' => 'Project Management', 'level' => QualificationLevel::Beginner],
            ],
            'Equipment Maintenance Window' => [
                ['qualification' => 'Safety Training', 'level' => QualificationLevel::Intermediate],
                ['qualification' => 'Forklift Certified', 'level' => QualificationLevel::Advanced],
            ],
            'Cross-Team Process Optimization' => [
                ['qualification' => 'Project Management', 'level' => QualificationLevel::Advanced],
                ['qualification' => 'Workshop Facilitation', 'level' => QualificationLevel::Intermediate],
            ],
            'Emergency Drill Coordination' => [
                ['qualification' => 'Safety Training', 'level' => QualificationLevel::Expert],
            ],
        ];

        foreach ($plans as $taskTitle => $entries) {
            $task = $tasks->get($taskTitle);

            if (! $task) {
                continue;
            }

            foreach ($entries as $entry) {
                $qualification = $qualifications->get($entry['qualification']);

                if (! $qualification) {
                    continue;
                }

                TaskRequirement::query()->create([
                    'task_id' => $task->id,
                    'qualification_id' => $qualification->id,
                    'required_level' => $entry['level'],
                ]);
            }
        }
    }
}
