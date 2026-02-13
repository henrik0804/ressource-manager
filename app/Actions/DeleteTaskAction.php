<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\HasDependentRelationshipsException;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

final class DeleteTaskAction
{
    public function handle(Task $task, bool $confirmDependencyDeletion = false): void
    {
        $dependents = $this->countDependents($task);

        if ($dependents !== [] && ! $confirmDependencyDeletion) {
            throw new HasDependentRelationshipsException($dependents);
        }

        DB::transaction(function () use ($task): void {
            $task->requirements()->delete();
            $task->assignments()->delete();
            $task->delete();
        });
    }

    /**
     * @return array<string, int>
     */
    private function countDependents(Task $task): array
    {
        $dependents = [];

        $requirementsCount = $task->requirements()->count();
        if ($requirementsCount > 0) {
            $dependents['task_requirements'] = $requirementsCount;
        }

        $assignmentsCount = $task->assignments()->count();
        if ($assignmentsCount > 0) {
            $dependents['task_assignments'] = $assignmentsCount;
        }

        return $dependents;
    }
}
