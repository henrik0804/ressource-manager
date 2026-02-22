<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Concerns\CapacityHelper;
use App\Enums\AccessSection;
use App\Enums\AssigneeStatus;
use App\Enums\AssignmentSource;
use App\Models\Resource;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TaskStaffingController
{
    use CapacityHelper;

    public function __invoke(Request $request): Response
    {
        abort_unless(
            $request->user()?->canReadSection(AccessSection::ManualAssignment),
            403,
        );

        $search = $request->string('search')->toString();

        $tasks = Task::query()
            ->with(['assignments.resource', 'requirements.qualification'])
            ->when($search, fn ($query, $search) => $query
                ->where('title', 'like', "%{$search}%"))
            ->orderByRaw("
                CASE status
                    WHEN 'in_progress' THEN 0
                    WHEN 'planned' THEN 1
                    WHEN 'blocked' THEN 2
                    WHEN 'done' THEN 3
                END
            ")
            ->orderByRaw("
                CASE priority
                    WHEN 'urgent' THEN 0
                    WHEN 'high' THEN 1
                    WHEN 'medium' THEN 2
                    WHEN 'low' THEN 3
                END
            ")
            ->orderBy('starts_at')
            ->get();

        $staffingData = $tasks->map(fn (Task $task) => [
            'task' => $task->only('id', 'title', 'description', 'starts_at', 'ends_at', 'effort_value', 'effort_unit', 'priority', 'status'),
            'effort_hours' => $this->calculateEffortHours($task),
            'assigned_capacity_hours' => $this->calculateAssignedCapacity($task),
            'coverage_percentage' => $this->calculateCoverage($task),
            'assignments' => $task->assignments->map(fn ($assignment) => [
                'id' => $assignment->id,
                'task_id' => $assignment->task_id,
                'resource_id' => $assignment->resource_id,
                'resource_name' => $assignment->resource?->name,
                'resource_capacity_value' => $assignment->resource?->capacity_value,
                'resource_capacity_unit' => $assignment->resource?->capacity_unit?->value,
                'allocation_ratio' => $assignment->allocation_ratio,
                'starts_at' => $assignment->starts_at?->toDateTimeString(),
                'ends_at' => $assignment->ends_at?->toDateTimeString(),
                'assignment_source' => $assignment->assignment_source->value,
                'assignee_status' => $assignment->assignee_status?->value,
                'contributed_hours' => $this->calculateAssignmentContribution($task, $assignment),
            ])->values(),
            'requirements' => $task->requirements->map(fn ($requirement) => [
                'id' => $requirement->id,
                'qualification_name' => $requirement->qualification?->name,
                'required_level' => $requirement->required_level?->value,
            ])->values(),
        ])->values();

        $canWrite = $request->user()?->canWriteSection(AccessSection::ManualAssignment) ?? false;

        $formData = [];

        if ($canWrite) {
            $formData = [
                'allTasks' => Task::query()->orderBy('title')->get(['id', 'title']),
                'resources' => Resource::query()->orderBy('name')->get(['id', 'name', 'capacity_value', 'capacity_unit']),
                'assignmentSources' => collect(AssignmentSource::cases())
                    ->map(fn (AssignmentSource $source) => ['value' => $source->value, 'label' => $source->label()]),
                'assigneeStatuses' => collect(AssigneeStatus::cases())
                    ->map(fn (AssigneeStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
            ];
        }

        return Inertia::render('task-staffing/Index', [
            'tasks' => $staffingData,
            'search' => $search,
            'canWrite' => $canWrite,
            ...$formData,
        ]);
    }

    /**
     * Calculate the total effort for a task in hours.
     */
    private function calculateEffortHours(Task $task): float
    {
        if (! $task->effort_value || ! $task->effort_unit) {
            return 0.0;
        }

        return round($task->effort_unit->toHours((float) $task->effort_value), 2);
    }

    /**
     * Calculate the total assigned capacity for a task in hours.
     */
    private function calculateAssignedCapacity(Task $task): float
    {
        $totalHours = 0.0;

        foreach ($task->assignments as $assignment) {
            $totalHours += $this->calculateAssignmentContribution($task, $assignment);
        }

        return round($totalHours, 2);
    }

    /**
     * Calculate how many hours a single assignment contributes to a task.
     */
    private function calculateAssignmentContribution(Task $task, \App\Models\TaskAssignment $assignment): float
    {
        $resource = $assignment->resource;

        if (! $resource) {
            return 0.0;
        }

        $assignmentStart = $assignment->starts_at ?? $task->starts_at;
        $assignmentEnd = $assignment->ends_at ?? $task->ends_at;

        if (! $assignmentStart || ! $assignmentEnd) {
            return 0.0;
        }

        $start = $this->toCarbon($assignmentStart);
        $end = $this->toCarbon($assignmentEnd);

        if ($end->lte($start)) {
            return 0.0;
        }

        $days = $this->countSpannedDays($start, $end);

        if ($days === 0) {
            return 0.0;
        }

        $capacityPerDay = $this->resolveCapacity($resource);
        $effectiveCapacity = $this->normalizeRatio($assignment->allocation_ratio) * $days;

        return round($effectiveCapacity, 2);
    }

    /**
     * Calculate the coverage percentage (assigned capacity / effort).
     */
    private function calculateCoverage(Task $task): float
    {
        $effortHours = $this->calculateEffortHours($task);

        if ($effortHours <= 0) {
            return 0.0;
        }

        $assignedHours = $this->calculateAssignedCapacity($task);

        return round(($assignedHours / $effortHours) * 100, 1);
    }
}
