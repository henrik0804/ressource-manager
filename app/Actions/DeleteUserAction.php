<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\HasDependentRelationshipsException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteUserAction
{
    public function handle(User $user, bool $confirmDependencyDeletion = false): void
    {
        $dependents = $this->countDependents($user);

        if ($dependents !== [] && ! $confirmDependencyDeletion) {
            throw new HasDependentRelationshipsException($dependents);
        }

        DB::transaction(function () use ($user): void {
            $resource = $user->resource;

            if ($resource) {
                $resource->resourceQualifications()->delete();
                $resource->taskAssignments()->delete();
                $resource->resourceAbsences()->delete();
                $resource->delete();
            }

            $user->delete();
        });
    }

    /**
     * @return array<string, int>
     */
    private function countDependents(User $user): array
    {
        $dependents = [];

        if ($user->resource()->exists()) {
            $dependents['resource'] = 1;
        }

        return $dependents;
    }
}
