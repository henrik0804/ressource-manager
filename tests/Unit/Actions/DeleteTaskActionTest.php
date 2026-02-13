<?php

declare(strict_types=1);

use App\Actions\DeleteTaskAction;
use App\Exceptions\HasDependentRelationshipsException;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;

test('delete task without dependents succeeds without confirmation', function (): void {
    $task = Task::factory()->create();

    app(DeleteTaskAction::class)->handle($task);

    expect(Task::query()->count())->toBe(0);
});

test('delete task with dependents throws without confirmation', function (): void {
    $task = Task::factory()->create();
    TaskRequirement::factory()->create(['task_id' => $task->id]);

    app(DeleteTaskAction::class)->handle($task);
})->throws(HasDependentRelationshipsException::class);

test('delete task with dependents returns dependent counts', function (): void {
    $task = Task::factory()->create();
    TaskRequirement::factory()->count(2)->create(['task_id' => $task->id]);
    TaskAssignment::factory()->count(3)->create(['task_id' => $task->id]);

    try {
        app(DeleteTaskAction::class)->handle($task);
    } catch (HasDependentRelationshipsException $e) {
        expect($e->dependents)->toBe([
            'task_requirements' => 2,
            'task_assignments' => 3,
        ]);

        return;
    }

    $this->fail('Expected HasDependentRelationshipsException was not thrown.');
});

test('delete task with confirmation cascades all dependents', function (): void {
    $task = Task::factory()->create();
    TaskRequirement::factory()->count(2)->create(['task_id' => $task->id]);
    TaskAssignment::factory()->count(3)->create(['task_id' => $task->id]);

    app(DeleteTaskAction::class)->handle($task, confirmDependencyDeletion: true);

    expect(Task::query()->count())->toBe(0);
    expect(TaskRequirement::query()->count())->toBe(0);
    expect(TaskAssignment::query()->count())->toBe(0);
});
