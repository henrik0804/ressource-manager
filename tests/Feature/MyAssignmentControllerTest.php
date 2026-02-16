<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskAssignment;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\from;

test('employees can view their own assignments', function (): void {
    $employee = actingAsUserWithPermissions([
        'read' => [AccessSection::EmployeeFeedback],
        'write' => [],
        'write_owned' => [AccessSection::EmployeeFeedback],
    ]);

    $resource = Resource::factory()->create(['user_id' => $employee->id]);
    $otherResource = Resource::factory()->create();

    $ownAssignment = TaskAssignment::factory()->create([
        'task_id' => Task::factory()->create()->id,
        'resource_id' => $resource->id,
    ]);

    $otherAssignment = TaskAssignment::factory()->create([
        'task_id' => Task::factory()->create()->id,
        'resource_id' => $otherResource->id,
    ]);

    $response = $this->get(route('my-assignments.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('my-assignments/Index')
        ->has('assignments.data', 1)
        ->where('assignments.data.0.id', $ownAssignment->id)
    );
});

test('employees can update their own assignment status', function (): void {
    $backUrl = '/my-assignments';
    $employee = actingAsUserWithPermissions([
        'read' => [AccessSection::EmployeeFeedback],
        'write' => [],
        'write_owned' => [AccessSection::EmployeeFeedback],
    ]);

    $resource = Resource::factory()->create(['user_id' => $employee->id]);
    $assignment = TaskAssignment::factory()->create([
        'task_id' => Task::factory()->create()->id,
        'resource_id' => $resource->id,
        'assignee_status' => 'pending',
    ]);

    from($backUrl)->put(route('my-assignments.update', $assignment), [
        'assignee_status' => 'in_progress',
    ])->assertRedirect($backUrl)->assertSessionHas('message', 'Status aktualisiert.');

    assertDatabaseHas('task_assignments', [
        'id' => $assignment->id,
        'assignee_status' => 'in_progress',
    ]);
});

test('employees cannot update other assignments via my-assignments', function (): void {
    $backUrl = '/my-assignments';
    actingAsUserWithPermissions([
        'read' => [AccessSection::EmployeeFeedback],
        'write' => [],
        'write_owned' => [AccessSection::EmployeeFeedback],
    ]);

    $otherResource = Resource::factory()->create();
    $otherAssignment = TaskAssignment::factory()->create([
        'task_id' => Task::factory()->create()->id,
        'resource_id' => $otherResource->id,
    ]);

    from($backUrl)->put(route('my-assignments.update', $otherAssignment), [
        'assignee_status' => 'in_progress',
    ])->assertForbidden();
});

test('users without employee feedback permission cannot access my-assignments', function (): void {
    actingAsUserWithPermissions([
        'read' => [AccessSection::ResourceManagement],
        'write' => [AccessSection::ResourceManagement],
        'write_owned' => [],
    ]);

    $this->get(route('my-assignments.index'))->assertForbidden();
});

test('employees can only update assignee_status field', function (): void {
    $backUrl = '/my-assignments';
    $employee = actingAsUserWithPermissions([
        'read' => [AccessSection::EmployeeFeedback],
        'write' => [],
        'write_owned' => [AccessSection::EmployeeFeedback],
    ]);

    $resource = Resource::factory()->create(['user_id' => $employee->id]);
    $task = Task::factory()->create();
    $assignment = TaskAssignment::factory()->create([
        'task_id' => $task->id,
        'resource_id' => $resource->id,
        'assignee_status' => 'pending',
        'allocation_ratio' => 0.5,
    ]);

    from($backUrl)->put(route('my-assignments.update', $assignment), [
        'assignee_status' => 'done',
    ])->assertRedirect($backUrl);

    assertDatabaseHas('task_assignments', [
        'id' => $assignment->id,
        'assignee_status' => 'done',
        'allocation_ratio' => 0.5,
    ]);
});
