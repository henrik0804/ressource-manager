<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Enums\AssignmentSource;
use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;
use Carbon\CarbonImmutable;

use function Pest\Laravel\postJson;

test('auto assign controller creates automated assignments', function (): void {
    actingAsUserWithPermissions([
        'write' => [AccessSection::AutomatedAssignment],
    ]);

    $qualification = Qualification::factory()->create();

    $task = Task::factory()->create([
        'starts_at' => CarbonImmutable::parse('2026-02-10 08:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-02-12 18:00:00'),
        'effort_value' => 16,
        'effort_unit' => 'hours',
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $task->id,
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
        'level' => QualificationLevel::Intermediate,
    ]);

    $response = postJson(route('task-assignments.auto-assign'));

    $response
        ->assertSuccessful()
        ->assertJson([
            'assigned' => 1,
            'skipped' => 0,
        ])
        ->assertJsonCount(1, 'assigned_tasks')
        ->assertJsonPath('assigned_tasks.0.task.id', $task->id)
        ->assertJsonPath('assigned_tasks.0.resources.0.id', $resource->id)
        ->assertJsonPath('assigned_tasks.0.task.effort_value', 16)
        ->assertJsonPath('assigned_tasks.0.task.effort_unit', 'hours')
        ->assertJsonCount(0, 'skipped_tasks');

    $assignment = TaskAssignment::query()->where('task_id', $task->id)->first();

    expect($assignment)->not->toBeNull();
    expect($assignment->resource_id)->toBe($resource->id);
    expect($assignment->assignment_source)->toBe(AssignmentSource::Automated);
});

test('auto assign controller returns suggestions when conflicts block assignment', function (): void {
    actingAsUserWithPermissions([
        'write' => [AccessSection::AutomatedAssignment],
    ]);

    $qualification = Qualification::factory()->create();

    $highPriorityTask = Task::factory()->create([
        'title' => 'Wichtige Aufgabe',
        'priority' => 'high',
        'starts_at' => CarbonImmutable::parse('2026-03-01 08:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-03-05 18:00:00'),
        'effort_value' => 16,
        'effort_unit' => 'hours',
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $highPriorityTask->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    $resource = Resource::factory()->create([
        'name' => 'Max Mustermann',
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Intermediate,
    ]);

    $lowPriorityTask = Task::factory()->create([
        'title' => 'Weniger wichtig',
        'priority' => 'low',
        'starts_at' => CarbonImmutable::parse('2026-03-01 08:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-03-05 18:00:00'),
    ]);

    TaskAssignment::factory()->create([
        'task_id' => $lowPriorityTask->id,
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-01 08:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-03-05 18:00:00'),
        'allocation_ratio' => 8,
        'assignment_source' => AssignmentSource::Manual,
    ]);

    $response = postJson(route('task-assignments.auto-assign'));

    $response->assertSuccessful();

    $data = $response->json();

    expect($data)
        ->toHaveKeys(['assigned', 'skipped', 'assigned_tasks', 'skipped_tasks', 'suggestions'])
        ->assigned->toBe(0)
        ->skipped->toBe(1);

    expect($data['skipped_tasks'])->toHaveCount(1);
    expect($data['skipped_tasks'][0]['task']['id'])->toBe($highPriorityTask->id);
    expect($data['skipped_tasks'][0]['reason'])->toBe('resource_conflicts');

    expect($data['suggestions'])->toHaveCount(1);

    $suggestion = $data['suggestions'][0];

    $taskData = $suggestion['task'];

    expect($taskData)
        ->toHaveKeys(['id', 'title', 'priority', 'starts_at', 'ends_at'])
        ->id->toBe($highPriorityTask->id)
        ->title->toBe('Wichtige Aufgabe')
        ->priority->toBe('high');

    expect($suggestion['resources'])->toHaveCount(1);

    $candidate = $suggestion['resources'][0];

    expect($candidate['resource'])
        ->toHaveKeys(['id', 'name', 'utilization_percentage'])
        ->id->toBe($resource->id)
        ->name->toBe('Max Mustermann');

    expect($candidate['conflict_types'])->toBeArray();
    expect($candidate['blocking_assignments'])->toHaveCount(1);

    expect($candidate['blocking_assignments'][0])
        ->toHaveKeys(['id', 'task_id', 'task_title', 'task_priority', 'starts_at', 'ends_at', 'assignment_source'])
        ->task_id->toBe($lowPriorityTask->id)
        ->task_title->toBe('Weniger wichtig')
        ->task_priority->toBe('low');
});

test('auto assign controller reschedules lower priority tasks when allowed', function (): void {
    actingAsUserWithPermissions([
        'write' => [AccessSection::AutomatedAssignment, AccessSection::PriorityScheduling],
    ]);

    $qualification = Qualification::factory()->create();

    $taskStartsAt = CarbonImmutable::parse('2026-05-01 00:00:00');
    $taskEndsAt = CarbonImmutable::parse('2026-05-02 00:00:00');

    $highPriorityTask = Task::factory()->create([
        'title' => 'Priorisiert',
        'priority' => 'high',
        'status' => 'planned',
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
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
        'level' => QualificationLevel::Intermediate,
    ]);

    $lowPriorityTask = Task::factory()->create([
        'title' => 'Später',
        'priority' => 'low',
        'status' => 'planned',
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
    ]);

    $lowPriorityAssignment = TaskAssignment::factory()->create([
        'task_id' => $lowPriorityTask->id,
        'resource_id' => $resource->id,
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'allocation_ratio' => 8,
        'assignment_source' => AssignmentSource::Manual,
    ]);

    $response = postJson(route('task-assignments.auto-assign'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('assigned', 1)
        ->assertJsonPath('skipped', 0)
        ->assertJsonPath('rescheduled.0.assignment_id', $lowPriorityAssignment->id)
        ->assertJsonCount(1, 'assigned_tasks')
        ->assertJsonPath('assigned_tasks.0.task.id', $highPriorityTask->id)
        ->assertJsonPath('assigned_tasks.0.resources.0.id', $resource->id)
        ->assertJsonCount(0, 'skipped_tasks');

    $lowPriorityAssignment->refresh();

    expect($lowPriorityAssignment->starts_at?->toDateString())->toBe('2026-05-02');
    expect($lowPriorityAssignment->ends_at?->toDateString())->toBe('2026-05-03');
});

test('auto assign controller requires automated assignment permission', function (): void {
    actingAsUserWithPermissions([
        'write' => [],
    ]);

    postJson(route('task-assignments.auto-assign'))
        ->assertForbidden();
});
