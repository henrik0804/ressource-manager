<?php

declare(strict_types=1);

use App\Actions\AutoAssignAction;
use App\Enums\AssignmentSource;
use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;
use Carbon\CarbonImmutable;

test('auto assign action chooses the lowest utilization resource', function (): void {
    $qualification = Qualification::factory()->create();

    $taskStartsAt = CarbonImmutable::parse('2026-02-10 08:00:00');
    $taskEndsAt = CarbonImmutable::parse('2026-02-12 18:00:00');

    $task = Task::factory()->create([
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'effort_value' => 16,
        'effort_unit' => 'hours',
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    $busyResource = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);
    $freeResource = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $busyResource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Advanced,
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $freeResource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Intermediate,
    ]);

    $busyTask = Task::factory()->create([
        'starts_at' => CarbonImmutable::parse('2026-02-11 08:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-02-11 12:00:00'),
    ]);

    TaskAssignment::factory()->create([
        'task_id' => $busyTask->id,
        'resource_id' => $busyResource->id,
        'starts_at' => $busyTask->starts_at,
        'ends_at' => $busyTask->ends_at,
        'allocation_ratio' => 0.5,
        'assignment_source' => AssignmentSource::Manual,
    ]);

    $result = app(AutoAssignAction::class)->handle();

    expect($result['assigned'])->toBe(1);
    expect($result['skipped'])->toBe(0);
    expect($result['suggestions'])->toBe([]);

    expect($result['assigned_tasks'])->toHaveCount(1);
    expect($result['assigned_tasks'][0]['task']['id'])->toBe($task->id);
    expect($result['assigned_tasks'][0]['resources'])->toHaveCount(1);
    expect($result['assigned_tasks'][0]['resources'][0]['id'])->toBe($freeResource->id);
    expect($result['assigned_tasks'][0]['resources'][0]['name'])->toBe($freeResource->name);
    expect($result['assigned_tasks'][0]['resources'][0]['allocation_ratio'])->toBe(5.33);
    expect($result['assigned_tasks'][0]['task']['effort_value'])->toBe(16.0);
    expect($result['assigned_tasks'][0]['task']['effort_unit'])->toBe('hours');

    $assignment = TaskAssignment::query()->where('task_id', $task->id)->first();

    expect($assignment)->not->toBeNull();
    expect($assignment->resource_id)->toBe($freeResource->id);
    expect($assignment->assignment_source)->toBe(AssignmentSource::Automated);
    expect((float) $assignment->allocation_ratio)->toBe(5.33);
});

test('auto assign action returns shift suggestions for higher priority tasks', function (): void {
    $qualification = Qualification::factory()->create();

    $taskStartsAt = CarbonImmutable::parse('2026-02-14 08:00:00');
    $taskEndsAt = CarbonImmutable::parse('2026-02-14 18:00:00');

    $highPriorityTask = Task::factory()->create([
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'priority' => 'urgent',
        'effort_value' => 8,
        'effort_unit' => 'hours',
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $highPriorityTask->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    $resource = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Advanced,
    ]);

    $lowPriorityTask = Task::factory()->create([
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'priority' => 'low',
    ]);

    TaskAssignment::factory()->create([
        'task_id' => $lowPriorityTask->id,
        'resource_id' => $resource->id,
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'allocation_ratio' => 8,
        'assignment_source' => AssignmentSource::Manual,
    ]);

    $result = app(AutoAssignAction::class)->handle();

    expect($result['assigned'])->toBe(0);
    expect($result['skipped'])->toBe(1);
    expect($result['suggestions'])->toHaveCount(1);
    expect($result['suggestions'][0]['task']['id'])->toBe($highPriorityTask->id);
    expect($result['suggestions'][0]['resources'][0]['blocking_assignments'][0]['task_id'])->toBe($lowPriorityTask->id);

    expect($result['skipped_tasks'])->toHaveCount(1);
    expect($result['skipped_tasks'][0]['task']['id'])->toBe($highPriorityTask->id);
    expect($result['skipped_tasks'][0]['reason'])->toBe('resource_conflicts');
});

test('auto assign action reschedules lower priority assignments when enabled', function (): void {
    $qualification = Qualification::factory()->create();

    $taskStartsAt = CarbonImmutable::parse('2026-04-01 00:00:00');
    $taskEndsAt = CarbonImmutable::parse('2026-04-02 00:00:00');

    $highPriorityTask = Task::factory()->create([
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'priority' => 'urgent',
        'status' => 'planned',
        'effort_value' => 8,
        'effort_unit' => 'hours',
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $highPriorityTask->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    $resource = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Advanced,
    ]);

    $lowPriorityTask = Task::factory()->create([
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'priority' => 'low',
        'status' => 'planned',
    ]);

    $lowPriorityAssignment = TaskAssignment::factory()->create([
        'task_id' => $lowPriorityTask->id,
        'resource_id' => $resource->id,
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'allocation_ratio' => 8,
        'assignment_source' => AssignmentSource::Manual,
    ]);

    $result = app(AutoAssignAction::class)->handle(true);

    expect($result['assigned'])->toBe(1);
    expect($result['skipped'])->toBe(0);
    expect($result['rescheduled'])->toHaveCount(1);

    expect($result['assigned_tasks'])->toHaveCount(1);
    expect($result['assigned_tasks'][0]['task']['id'])->toBe($highPriorityTask->id);
    expect($result['assigned_tasks'][0]['resources'])->toHaveCount(1);
    expect($result['assigned_tasks'][0]['resources'][0]['id'])->toBe($resource->id);

    $lowPriorityAssignment->refresh();

    expect($lowPriorityAssignment->starts_at?->toDateString())->toBe('2026-04-02');
    expect($lowPriorityAssignment->ends_at?->toDateString())->toBe('2026-04-03');

    $assignment = TaskAssignment::query()->where('task_id', $highPriorityTask->id)->first();
    expect($assignment)->not->toBeNull();
    expect($assignment->resource_id)->toBe($resource->id);
});

test('auto assign action skips slot resources', function (): void {
    $qualification = Qualification::factory()->create();

    $task = Task::factory()->create([
        'starts_at' => CarbonImmutable::parse('2026-06-01 08:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-06-02 18:00:00'),
        'effort_value' => 8,
        'effort_unit' => 'hours',
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Intermediate,
    ]);

    $result = app(AutoAssignAction::class)->handle();

    expect($result['assigned'])->toBe(0);
    expect($result['skipped'])->toBe(1);
    expect($result['suggestions'])->toBe([]);

    expect($result['skipped_tasks'])->toHaveCount(1);
    expect($result['skipped_tasks'][0]['task']['id'])->toBe($task->id);
    expect($result['skipped_tasks'][0]['reason'])->toBe('insufficient_capacity');

    expect(TaskAssignment::query()->where('task_id', $task->id)->exists())->toBeFalse();
});

test('auto assign action reports skip reason for unqualified resources', function (): void {
    $qualification = Qualification::factory()->create();

    $task = Task::factory()->create([
        'starts_at' => CarbonImmutable::parse('2026-06-10 08:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-06-11 18:00:00'),
        'effort_value' => 8,
        'effort_unit' => 'hours',
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Expert,
    ]);

    $result = app(AutoAssignAction::class)->handle();

    expect($result['assigned'])->toBe(0);
    expect($result['skipped'])->toBe(1);
    expect($result['skipped_tasks'])->toHaveCount(1);
    expect($result['skipped_tasks'][0]['task']['id'])->toBe($task->id);
    expect($result['skipped_tasks'][0]['task']['title'])->toBe($task->title);
    expect($result['skipped_tasks'][0]['reason'])->toBe('no_qualified_resources');
});

test('auto assign action splits allocations across multiple resources', function (): void {
    $qualification = Qualification::factory()->create();

    $task = Task::factory()->create([
        'starts_at' => CarbonImmutable::parse('2026-06-03 08:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-06-04 18:00:00'),
        'effort_value' => 32,
        'effort_unit' => 'hours',
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    $resourceA = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $resourceA->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Intermediate,
    ]);

    $resourceB = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $resourceB->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Intermediate,
    ]);

    $result = app(AutoAssignAction::class)->handle();

    expect($result['assigned'])->toBe(1);
    expect($result['skipped'])->toBe(0);
    expect($result['suggestions'])->toBe([]);

    expect($result['assigned_tasks'])->toHaveCount(1);
    expect($result['assigned_tasks'][0]['resources'])->toHaveCount(2);

    $assignedResourceIds = array_column($result['assigned_tasks'][0]['resources'], 'id');
    expect($assignedResourceIds)->toContain($resourceA->id);
    expect($assignedResourceIds)->toContain($resourceB->id);

    $assignments = TaskAssignment::query()->where('task_id', $task->id)->get();

    expect($assignments)->toHaveCount(2);
    expect((float) $assignments->sum('allocation_ratio'))->toBe(16.0);
    expect($assignments->pluck('allocation_ratio')->map(fn ($value) => (float) $value)->unique()->values()->all())
        ->toBe([8.0]);
});
