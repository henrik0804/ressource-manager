<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskAssignment;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

test('guests are redirected from the task staffing page', function (): void {
    get(route('task-staffing'))->assertRedirect(route('login'));
});

test('users without ManualAssignment permission are forbidden', function (): void {
    actingAsUserWithPermissions([
        'read' => [AccessSection::ResourceManagement],
        'write' => [],
    ]);

    get(route('task-staffing'))->assertForbidden();
});

describe('authorized users', function (): void {
    beforeEach(function (): void {
        actingAsUserWithPermissions([
            'read' => [AccessSection::ManualAssignment],
        ]);
    });

    test('can view the task staffing page', function (): void {
        Task::factory()->count(3)->create();

        get(route('task-staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('task-staffing/Index')
                ->has('tasks', 3)
                ->has('search')
            );
    });

    test('task staffing returns expected data structure', function (): void {
        $resource = Resource::factory()->create([
            'capacity_value' => 8,
            'capacity_unit' => 'hours_per_day',
        ]);

        $task = Task::factory()->create([
            'effort_value' => 40,
            'effort_unit' => 'hours',
            'starts_at' => CarbonImmutable::parse('2026-03-01'),
            'ends_at' => CarbonImmutable::parse('2026-03-11'),
        ]);

        TaskAssignment::factory()->create([
            'task_id' => $task->id,
            'resource_id' => $resource->id,
            'starts_at' => CarbonImmutable::parse('2026-03-01'),
            'ends_at' => CarbonImmutable::parse('2026-03-11'),
            'allocation_ratio' => 0.5,
        ]);

        get(route('task-staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('task-staffing/Index')
                ->has('tasks', 1, fn (Assert $item) => $item
                    ->has('task.id')
                    ->has('task.title')
                    ->has('task.effort_value')
                    ->has('task.effort_unit')
                    ->has('task.priority')
                    ->has('task.status')
                    ->has('effort_hours')
                    ->has('assigned_capacity_hours')
                    ->has('coverage_percentage')
                    ->has('assignments', 1)
                    ->has('requirements')
                )
            );
    });

    test('effort hours are calculated correctly for hours unit', function (): void {
        Task::factory()->create([
            'effort_value' => 40,
            'effort_unit' => 'hours',
        ]);

        get(route('task-staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks', 1, fn (Assert $item) => $item
                    ->where('effort_hours', fn ($value) => (float) $value === 40.0)
                    ->etc()
                )
            );
    });

    test('effort hours are calculated correctly for days unit', function (): void {
        Task::factory()->create([
            'effort_value' => 5,
            'effort_unit' => 'days',
        ]);

        get(route('task-staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks', 1, fn (Assert $item) => $item
                    ->where('effort_hours', fn ($value) => (float) $value === 40.0) // 5 days * 8 hours
                    ->etc()
                )
            );
    });

    test('coverage percentage reflects assigned capacity vs effort', function (): void {
        $resource = Resource::factory()->create([
            'capacity_value' => 8,
            'capacity_unit' => 'hours_per_day',
        ]);

        $task = Task::factory()->create([
            'effort_value' => 80,
            'effort_unit' => 'hours',
            'starts_at' => CarbonImmutable::parse('2026-03-01'),
            'ends_at' => CarbonImmutable::parse('2026-03-11'),
        ]);

        TaskAssignment::factory()->create([
            'task_id' => $task->id,
            'resource_id' => $resource->id,
            'starts_at' => CarbonImmutable::parse('2026-03-01'),
            'ends_at' => CarbonImmutable::parse('2026-03-11'),
            'allocation_ratio' => 0.5,
        ]);

        get(route('task-staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks', 1, fn (Assert $item) => $item
                    ->where('effort_hours', fn ($value) => (float) $value === 80.0)
                    ->where('assigned_capacity_hours', fn ($value) => (float) $value === 5.0) // 0.5 ratio * 10 days
                    ->where('coverage_percentage', 6.3) // 5.0 / 80.0 * 100
                    ->etc()
                )
            );
    });

    test('assigned capacity counts each spanned day with time ranges', function (): void {
        $resource = Resource::factory()->create([
            'capacity_value' => 8,
            'capacity_unit' => 'hours_per_day',
        ]);

        $task = Task::factory()->create([
            'effort_value' => 16,
            'effort_unit' => 'hours',
            'starts_at' => CarbonImmutable::parse('2026-03-01 08:00:00'),
            'ends_at' => CarbonImmutable::parse('2026-03-03 00:00:00'),
        ]);

        TaskAssignment::factory()->create([
            'task_id' => $task->id,
            'resource_id' => $resource->id,
            'starts_at' => CarbonImmutable::parse('2026-03-01 08:00:00'),
            'ends_at' => CarbonImmutable::parse('2026-03-02 16:00:00'),
            'allocation_ratio' => 8,
        ]);

        get(route('task-staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks', 1, fn (Assert $item) => $item
                    ->where('assigned_capacity_hours', fn ($value) => (float) $value === 16.0)
                    ->etc()
                )
            );
    });

    test('tasks with no effort show zero coverage', function (): void {
        Task::factory()->create([
            'effort_value' => 0,
            'effort_unit' => 'hours',
        ]);

        get(route('task-staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks', 1, fn (Assert $item) => $item
                    ->where('coverage_percentage', fn ($value) => (float) $value === 0.0)
                    ->etc()
                )
            );
    });

    test('search filters tasks by title', function (): void {
        Task::factory()->create(['title' => 'Frontend Development']);
        Task::factory()->create(['title' => 'Backend API']);

        get(route('task-staffing', ['search' => 'Frontend']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks', 1)
                ->where('tasks.0.task.title', 'Frontend Development')
                ->where('search', 'Frontend')
            );
    });

    test('multiple assignments are aggregated per task', function (): void {
        $task = Task::factory()->create([
            'effort_value' => 100,
            'effort_unit' => 'hours',
            'starts_at' => CarbonImmutable::parse('2026-03-01'),
            'ends_at' => CarbonImmutable::parse('2026-03-11'),
        ]);

        $resourceA = Resource::factory()->create(['capacity_value' => 8, 'capacity_unit' => 'hours_per_day']);
        $resourceB = Resource::factory()->create(['capacity_value' => 8, 'capacity_unit' => 'hours_per_day']);

        TaskAssignment::factory()->create([
            'task_id' => $task->id,
            'resource_id' => $resourceA->id,
            'starts_at' => CarbonImmutable::parse('2026-03-01'),
            'ends_at' => CarbonImmutable::parse('2026-03-11'),
            'allocation_ratio' => 0.5,
        ]);

        TaskAssignment::factory()->create([
            'task_id' => $task->id,
            'resource_id' => $resourceB->id,
            'starts_at' => CarbonImmutable::parse('2026-03-01'),
            'ends_at' => CarbonImmutable::parse('2026-03-11'),
            'allocation_ratio' => 1.0,
        ]);

        get(route('task-staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks', 1, fn (Assert $item) => $item
                    ->has('assignments', 2)
                    ->where('assigned_capacity_hours', fn ($value) => (float) $value === 15.0) // (0.5 * 10) + (1.0 * 10)
                    ->etc()
                )
            );
    });
});
