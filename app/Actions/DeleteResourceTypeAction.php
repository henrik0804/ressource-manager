<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\HasDependentRelationshipsException;
use App\Models\ResourceType;
use Illuminate\Support\Facades\DB;

final class DeleteResourceTypeAction
{
    public function handle(ResourceType $resourceType, bool $confirmDependencyDeletion = false): void
    {
        $dependents = $this->countDependents($resourceType);

        if ($dependents !== [] && ! $confirmDependencyDeletion) {
            throw new HasDependentRelationshipsException($dependents);
        }

        DB::transaction(function () use ($resourceType): void {
            foreach ($resourceType->resources as $resource) {
                $resource->resourceQualifications()->delete();
                $resource->taskAssignments()->delete();
                $resource->resourceAbsences()->delete();
                $resource->delete();
            }

            foreach ($resourceType->qualifications as $qualification) {
                $qualification->resourceQualifications()->delete();
                $qualification->taskRequirements()->delete();
                $qualification->delete();
            }

            $resourceType->delete();
        });
    }

    /**
     * @return array<string, int>
     */
    private function countDependents(ResourceType $resourceType): array
    {
        $dependents = [];

        $resourcesCount = $resourceType->resources()->count();
        if ($resourcesCount > 0) {
            $dependents['resources'] = $resourcesCount;
        }

        $qualificationsCount = $resourceType->qualifications()->count();
        if ($qualificationsCount > 0) {
            $dependents['qualifications'] = $qualificationsCount;
        }

        return $dependents;
    }
}
