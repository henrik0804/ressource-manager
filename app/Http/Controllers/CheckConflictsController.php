<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CheckConflictsRequest;
use App\Models\Resource;
use App\Models\Task;
use App\Services\ConflictDetectionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

final class CheckConflictsController
{
    public function __invoke(CheckConflictsRequest $request, ConflictDetectionService $service): JsonResponse
    {
        $validated = $request->validated();

        $resource = Resource::findOrFail($validated['resource_id']);

        $startsAt = $this->resolveStartsAt($validated);
        $endsAt = $this->resolveEndsAt($validated);

        if ($startsAt === null || $endsAt === null) {
            return response()->json(['has_conflicts' => false, 'conflicts' => []]);
        }

        $report = $service->detect(
            resource: $resource,
            startsAt: $startsAt,
            endsAt: $endsAt,
            allocationRatio: $validated['allocation_ratio'] ?? null,
            excludeAssignmentId: isset($validated['exclude_assignment_id']) ? (int) $validated['exclude_assignment_id'] : null,
        );

        return response()->json([
            'has_conflicts' => $report->hasConflicts(),
            'conflicts' => $report->toArray(),
        ]);
    }

    /**
     * Resolve the start date from explicit assignment dates or fall back to the task's dates.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveStartsAt(array $validated): ?CarbonImmutable
    {
        if (! empty($validated['starts_at'])) {
            return CarbonImmutable::parse($validated['starts_at']);
        }

        return $this->resolveTaskDate($validated, 'starts_at');
    }

    /**
     * Resolve the end date from explicit assignment dates or fall back to the task's dates.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveEndsAt(array $validated): ?CarbonImmutable
    {
        if (! empty($validated['ends_at'])) {
            return CarbonImmutable::parse($validated['ends_at']);
        }

        return $this->resolveTaskDate($validated, 'ends_at');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveTaskDate(array $validated, string $field): ?CarbonImmutable
    {
        if (empty($validated['task_id'])) {
            return null;
        }

        $task = Task::find($validated['task_id']);

        if ($task === null || $task->{$field} === null) {
            return null;
        }

        return CarbonImmutable::instance($task->{$field});
    }
}
