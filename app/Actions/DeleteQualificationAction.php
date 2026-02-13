<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\HasDependentRelationshipsException;
use App\Models\Qualification;
use Illuminate\Support\Facades\DB;

final class DeleteQualificationAction
{
    public function handle(Qualification $qualification, bool $confirmDependencyDeletion = false): void
    {
        $dependents = $this->countDependents($qualification);

        if ($dependents !== [] && ! $confirmDependencyDeletion) {
            throw new HasDependentRelationshipsException($dependents);
        }

        DB::transaction(function () use ($qualification): void {
            $qualification->resourceQualifications()->delete();
            $qualification->taskRequirements()->delete();
            $qualification->delete();
        });
    }

    /**
     * @return array<string, int>
     */
    private function countDependents(Qualification $qualification): array
    {
        $dependents = [];

        $resourceQualificationsCount = $qualification->resourceQualifications()->count();
        if ($resourceQualificationsCount > 0) {
            $dependents['resource_qualifications'] = $resourceQualificationsCount;
        }

        $taskRequirementsCount = $qualification->taskRequirements()->count();
        if ($taskRequirementsCount > 0) {
            $dependents['task_requirements'] = $taskRequirementsCount;
        }

        return $dependents;
    }
}
