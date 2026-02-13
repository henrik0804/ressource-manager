<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\HasDependentRelationshipsException;
use App\Models\Resource;
use Illuminate\Support\Facades\DB;

final class DeleteResourceAction
{
    public function handle(Resource $resource, bool $confirmDependencyDeletion = false): void
    {
        $dependents = $this->countDependents($resource);

        if ($dependents !== [] && ! $confirmDependencyDeletion) {
            throw new HasDependentRelationshipsException($dependents);
        }

        DB::transaction(function () use ($resource): void {
            $resource->resourceQualifications()->delete();
            $resource->taskAssignments()->delete();
            $resource->resourceAbsences()->delete();
            $resource->delete();
        });
    }

    /**
     * @return array<string, int>
     */
    private function countDependents(Resource $resource): array
    {
        $dependents = [];

        $qualificationsCount = $resource->resourceQualifications()->count();
        if ($qualificationsCount > 0) {
            $dependents['resource_qualifications'] = $qualificationsCount;
        }

        $assignmentsCount = $resource->taskAssignments()->count();
        if ($assignmentsCount > 0) {
            $dependents['task_assignments'] = $assignmentsCount;
        }

        $absencesCount = $resource->resourceAbsences()->count();
        if ($absencesCount > 0) {
            $dependents['resource_absences'] = $absencesCount;
        }

        return $dependents;
    }
}
