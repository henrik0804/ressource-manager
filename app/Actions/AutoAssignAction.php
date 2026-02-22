<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\CapacityHelper;
use App\Enums\AssignmentSource;
use App\Enums\CapacityUnit;
use App\Enums\ConflictType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;
use App\Services\ConflictDetectionService;
use App\Services\ConflictReport;
use App\Services\UtilizationService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final readonly class AutoAssignAction
{
    use CapacityHelper;

    public function __construct(
        private ConflictDetectionService $conflictDetection,
        private UtilizationService $utilizationService,
        private StoreTaskAssignmentAction $storeTaskAssignment,
        private UpdateTaskAssignmentAction $updateTaskAssignment,
    ) {}

    /**
     * @return array{assigned: int, skipped: int, assigned_tasks: list<array{task: array{id: int, title: string, priority: string, starts_at: string|null, ends_at: string|null, effort_value: float, effort_unit: string}, resources: list<array{id: int, name: string, allocation_ratio: float}>}>, skipped_tasks: list<array{task: array{id: int, title: string, priority: string, starts_at: string|null, ends_at: string|null}, reason: string}>, rescheduled: list<array{assignment_id: int, task_id: int, task_title: string, task_priority: string, previous_starts_at: string|null, previous_ends_at: string|null, starts_at: string|null, ends_at: string|null}>, suggestions: list<array{task: array{id: int, title: string, priority: string, starts_at: string|null, ends_at: string|null}, resources: list<array{resource: array{id: int, name: string, utilization_percentage: float|null}, conflict_types: list<string>, blocking_assignments: list<array{id: int, task_id: int, task_title: string, task_priority: string, starts_at: string|null, ends_at: string|null, assignment_source: string}>}>}>}
     */
    public function handle(bool $allowPriorityScheduling = false): array
    {
        $tasks = Task::query()
            ->whereDoesntHave('assignments')
            ->with('requirements')
            ->orderByRaw($this->priorityOrderSql())
            ->orderBy('starts_at')
            ->get();

        $assignedTasks = [];
        $skippedTasks = [];
        $suggestions = [];
        $rescheduled = [];

        foreach ($tasks as $task) {
            if ($task->starts_at === null || $task->ends_at === null) {
                $skippedTasks[] = [
                    'task' => $this->taskSummary($task),
                    'reason' => 'missing_dates',
                ];

                continue;
            }

            $effortHours = $this->effortHours($task);

            if ($effortHours === null || $effortHours <= 0) {
                $skippedTasks[] = [
                    'task' => $this->taskSummary($task),
                    'reason' => 'missing_effort',
                ];

                continue;
            }

            $durationDays = $this->durationDays($task->starts_at, $task->ends_at);
            $allocationPerDay = $this->allocationPerDay($effortHours, $durationDays);

            $candidateResources = $this->matchingResources($task->requirements);

            if ($candidateResources->isEmpty()) {
                $skippedTasks[] = [
                    'task' => $this->taskSummary($task),
                    'reason' => 'no_qualified_resources',
                ];

                continue;
            }

            $utilizationByResource = $this->utilizationByResource($task->starts_at, $task->ends_at);

            $rankedResources = $candidateResources
                ->sortBy(function ($resource) use ($utilizationByResource): float {
                    if (! $resource instanceof Resource) {
                        return PHP_FLOAT_MAX;
                    }

                    return $utilizationByResource[$resource->id] ?? PHP_FLOAT_MAX;
                })
                ->values();

            $taskSuggestions = [];
            $taskRescheduled = [];
            $taskAssignedResources = [];
            $assignedTask = false;
            $remainingAllocation = $allocationPerDay;

            try {
                DB::transaction(function () use (
                    $allowPriorityScheduling,
                    $rankedResources,
                    $task,
                    $utilizationByResource,
                    &$remainingAllocation,
                    &$taskRescheduled,
                    &$taskSuggestions,
                    &$taskAssignedResources,
                ): void {
                    foreach ($rankedResources as $resource) {
                        if (! $resource instanceof Resource) {
                            continue;
                        }
                        if ($remainingAllocation <= 0) {
                            break;
                        }

                        $requiredAllocation = $this->requiredAllocation($resource, $remainingAllocation);

                        if ($requiredAllocation === null) {
                            continue;
                        }

                        $report = $this->conflictDetection->detect(
                            resource: $resource,
                            startsAt: $task->starts_at,
                            endsAt: $task->ends_at,
                            allocationRatio: $requiredAllocation,
                        );

                        if (! $report->hasConflicts()) {
                            $this->storeTaskAssignment->handle([
                                'task_id' => $task->id,
                                'resource_id' => $resource->id,
                                'allocation_ratio' => $requiredAllocation,
                                'assignment_source' => AssignmentSource::Automated->value,
                            ]);

                            $taskAssignedResources[] = [
                                'id' => $resource->id,
                                'name' => $resource->name,
                                'allocation_ratio' => $requiredAllocation,
                            ];

                            $remainingAllocation = round($remainingAllocation - $requiredAllocation, 2);

                            continue;
                        }

                        $blockingAssignments = $this->blockingAssignments($task, $report);

                        if ($allowPriorityScheduling && $blockingAssignments->isNotEmpty()) {
                            $rescheduledAssignments = $this->attemptPriorityScheduling(
                                task: $task,
                                resource: $resource,
                                blockingAssignments: $blockingAssignments,
                                allocationPerDay: $requiredAllocation,
                            );

                            if ($rescheduledAssignments !== null) {
                                $taskRescheduled = array_merge($taskRescheduled, $rescheduledAssignments);

                                $taskAssignedResources[] = [
                                    'id' => $resource->id,
                                    'name' => $resource->name,
                                    'allocation_ratio' => $requiredAllocation,
                                ];

                                $remainingAllocation = round($remainingAllocation - $requiredAllocation, 2);

                                continue;
                            }
                        }

                        if ($blockingAssignments->isEmpty()) {
                            continue;
                        }

                        $taskSuggestions[] = [
                            'resource' => $this->resourceSummary(
                                $resource,
                                $utilizationByResource[$resource->id] ?? null,
                            ),
                            'conflict_types' => $this->conflictTypes($report),
                            'blocking_assignments' => $blockingAssignments
                                ->map(fn (TaskAssignment $assignment) => $this->assignmentSummary($assignment))
                                ->values()
                                ->all(),
                        ];
                    }

                    if ($remainingAllocation > 0) {
                        throw new RuntimeException('Unable to allocate full effort.');
                    }
                });

                $assignedTask = true;

                $assignedTasks[] = [
                    'task' => $this->taskEffortSummary($task),
                    'resources' => $taskAssignedResources,
                ];

                if ($taskRescheduled !== []) {
                    $rescheduled = array_merge($rescheduled, $taskRescheduled);
                }
            } catch (Throwable) {
                $assignedTask = false;
            }

            if (! $assignedTask) {
                $skippedTasks[] = [
                    'task' => $this->taskSummary($task),
                    'reason' => $taskSuggestions !== [] ? 'resource_conflicts' : 'insufficient_capacity',
                ];

                if ($taskSuggestions !== []) {
                    $suggestions[] = [
                        'task' => $this->taskSummary($task),
                        'resources' => $taskSuggestions,
                    ];
                }
            }
        }

        return [
            'assigned' => count($assignedTasks),
            'skipped' => count($skippedTasks),
            'assigned_tasks' => $assignedTasks,
            'skipped_tasks' => $skippedTasks,
            'rescheduled' => $rescheduled,
            'suggestions' => $suggestions,
        ];
    }

    private function priorityOrderSql(): string
    {
        return "case priority when 'urgent' then 1 when 'high' then 2 when 'medium' then 3 when 'low' then 4 else 5 end";
    }

    /**
     * @param  Collection<int, TaskRequirement>  $requirements
     * @return Collection<int, resource>
     */
    private function matchingResources(Collection $requirements): Collection
    {
        $query = Resource::query();

        foreach ($requirements as $requirement) {
            $query->whereHas('resourceQualifications', function (Builder $qualificationQuery) use ($requirement): void {
                $qualificationQuery->where('qualification_id', $requirement->qualification_id);

                if ($requirement->required_level !== null) {
                    $qualificationQuery->whereIn('level', $requirement->required_level->levelsAtLeast());
                }
            });
        }

        return $query
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, float>
     */
    private function utilizationByResource(DateTimeInterface $startsAt, DateTimeInterface $endsAt): array
    {
        $data = $this->utilizationService->calculate($startsAt, $endsAt);

        return collect($data['resources'])
            ->mapWithKeys(fn (array $resource) => [
                $resource['id'] => (float) $resource['summary']['utilization_percentage'],
            ])
            ->all();
    }

    /**
     * @return Collection<int, TaskAssignment>
     */
    private function blockingAssignments(Task $task, ConflictReport $report): Collection
    {
        $assignmentIds = $this->conflictAssignmentIds($report);

        if ($assignmentIds->isEmpty()) {
            return collect();
        }

        return TaskAssignment::query()
            ->with('task')
            ->whereIn('id', $assignmentIds)
            ->get()
            ->filter(function (TaskAssignment $assignment) use ($task): bool {
                $assignmentTask = $assignment->task;

                if ($assignmentTask === null) {
                    return false;
                }

                return $this->priorityRank($assignmentTask->priority) > $this->priorityRank($task->priority);
            });
    }

    /**
     * @return Collection<int, int>
     */
    private function conflictAssignmentIds(ConflictReport $report): Collection
    {
        $assignmentIds = collect();

        foreach ([ConflictType::DoubleBooked, ConflictType::Overloaded] as $type) {
            $assignmentIds = $assignmentIds->merge(
                $report
                    ->conflictsFor($type)
                    ->pluck('related_ids')
                    ->flatten()
                    ->filter()
            );
        }

        return $assignmentIds->unique()->values();
    }

    /**
     * @return list<string>
     */
    private function conflictTypes(ConflictReport $report): array
    {
        return collect($report->types())
            ->map(fn (ConflictType $type) => $type->value)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TaskAssignment>  $blockingAssignments
     * @return list<array{assignment_id: int, task_id: int, task_title: string, task_priority: string, previous_starts_at: string|null, previous_ends_at: string|null, starts_at: string|null, ends_at: string|null}>|null
     */
    private function attemptPriorityScheduling(
        Task $task,
        Resource $resource,
        Collection $blockingAssignments,
        float $allocationPerDay,
    ): ?array {
        if ($task->starts_at === null || $task->ends_at === null) {
            return null;
        }

        try {
            return DB::transaction(function () use ($task, $resource, $blockingAssignments, $allocationPerDay): array {
                $rescheduled = $this->rescheduleBlockingAssignments($task, $resource, $blockingAssignments);

                if ($rescheduled === null) {
                    throw new RuntimeException('Unable to reschedule blocking assignments.');
                }

                $report = $this->conflictDetection->detect(
                    resource: $resource,
                    startsAt: $task->starts_at,
                    endsAt: $task->ends_at,
                    allocationRatio: $allocationPerDay,
                );

                if ($report->hasConflicts()) {
                    throw new RuntimeException('Conflicts remain after rescheduling.');
                }

                $this->storeTaskAssignment->handle([
                    'task_id' => $task->id,
                    'resource_id' => $resource->id,
                    'allocation_ratio' => round($allocationPerDay, 2),
                    'assignment_source' => AssignmentSource::Automated->value,
                ]);

                return $rescheduled;
            });
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  Collection<int, TaskAssignment>  $blockingAssignments
     * @return list<array{assignment_id: int, task_id: int, task_title: string, task_priority: string, previous_starts_at: string|null, previous_ends_at: string|null, starts_at: string|null, ends_at: string|null}>|null
     */
    private function rescheduleBlockingAssignments(Task $priorityTask, Resource $resource, Collection $blockingAssignments): ?array
    {
        $priorityEndsAt = $priorityTask->ends_at;

        if ($priorityEndsAt === null) {
            return null;
        }

        $sortedAssignments = $blockingAssignments
            ->filter(fn (TaskAssignment $assignment) => $this->canShiftAssignment($assignment))
            ->sortBy(function (TaskAssignment $assignment): int {
                $startsAt = $assignment->starts_at ?? $assignment->task?->starts_at;

                return $startsAt?->getTimestamp() ?? PHP_INT_MAX;
            });

        if ($sortedAssignments->count() !== $blockingAssignments->count()) {
            return null;
        }

        $rescheduled = [];

        foreach ($sortedAssignments as $assignment) {
            $assignmentTask = $assignment->task;

            if ($assignmentTask === null) {
                return null;
            }

            $assignmentStartsAt = $assignment->starts_at ?? $assignmentTask->starts_at;
            $assignmentEndsAt = $assignment->ends_at ?? $assignmentTask->ends_at;

            if ($assignmentStartsAt === null || $assignmentEndsAt === null) {
                return null;
            }

            $durationMinutes = $this->durationMinutes($assignmentStartsAt, $assignmentEndsAt);

            if ($durationMinutes <= 0) {
                return null;
            }

            $earliestStart = $this->laterOf($priorityEndsAt, $assignmentStartsAt);

            $slot = $this->findNextAvailableSlot(
                resource: $resource,
                earliestStart: $earliestStart,
                durationMinutes: $durationMinutes,
                allocationRatio: $assignment->allocation_ratio,
                excludeAssignmentId: $assignment->id,
            );

            if ($slot === null) {
                return null;
            }

            $this->updateTaskAssignment->handle($assignment, [
                'starts_at' => $slot['starts_at'],
                'ends_at' => $slot['ends_at'],
            ]);

            $rescheduled[] = $this->rescheduledSummary(
                assignment: $assignment,
                previousStartsAt: $assignmentStartsAt,
                previousEndsAt: $assignmentEndsAt,
                startsAt: $slot['starts_at'],
                endsAt: $slot['ends_at'],
            );
        }

        return $rescheduled;
    }

    private function canShiftAssignment(TaskAssignment $assignment): bool
    {
        $task = $assignment->task;

        if ($task === null) {
            return false;
        }

        return in_array($task->status, [TaskStatus::Planned, TaskStatus::Blocked], true);
    }

    /**
     * @return array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}|null
     */
    private function findNextAvailableSlot(
        Resource $resource,
        CarbonImmutable $earliestStart,
        int $durationMinutes,
        float|int|string|null $allocationRatio,
        ?int $excludeAssignmentId,
        int $searchDays = 30,
    ): ?array {
        if ($durationMinutes <= 0) {
            return null;
        }

        $maxOffset = max($searchDays, 0);

        for ($offset = 0; $offset <= $maxOffset; $offset++) {
            $candidateStart = $earliestStart->addDays($offset);
            $candidateEnd = $candidateStart->addMinutes($durationMinutes);

            $report = $this->conflictDetection->detect(
                resource: $resource,
                startsAt: $candidateStart,
                endsAt: $candidateEnd,
                allocationRatio: $allocationRatio,
                excludeAssignmentId: $excludeAssignmentId,
            );

            if (! $report->hasConflicts()) {
                return [
                    'starts_at' => $candidateStart,
                    'ends_at' => $candidateEnd,
                ];
            }
        }

        return null;
    }

    private function durationMinutes(DateTimeInterface $startsAt, DateTimeInterface $endsAt): int
    {
        $start = $this->toCarbon($startsAt);
        $end = $this->toCarbon($endsAt);

        return (int) $start->diffInMinutes($end, false);
    }

    private function durationDays(DateTimeInterface $startsAt, DateTimeInterface $endsAt): int
    {
        $days = $this->countSpannedDays($startsAt, $endsAt);

        return max($days, 1);
    }

    private function effortHours(Task $task): ?float
    {
        if ($task->effort_value === null || $task->effort_unit === null) {
            return null;
        }

        return $task->effort_unit->toHours((float) $task->effort_value);
    }

    private function allocationPerDay(float $effortHours, int $durationDays): float
    {
        if ($durationDays <= 0) {
            return 0.0;
        }

        return round($effortHours / $durationDays, 2);
    }

    private function requiredAllocation(Resource $resource, float $allocationPerDay): ?float
    {
        if ($resource->capacity_unit !== CapacityUnit::HoursPerDay) {
            return null;
        }

        $capacityPerDay = $this->resolveCapacity($resource);

        if ($capacityPerDay <= 0 || $allocationPerDay <= 0) {
            return null;
        }

        $allocation = min($allocationPerDay, $capacityPerDay);

        return round($allocation, 2);
    }

    private function laterOf(DateTimeInterface $first, DateTimeInterface $second): CarbonImmutable
    {
        $firstCarbon = $this->toCarbon($first);
        $secondCarbon = $this->toCarbon($second);

        return $firstCarbon->gte($secondCarbon) ? $firstCarbon : $secondCarbon;
    }

    /**
     * @return array{id: int, title: string, priority: string, starts_at: string|null, ends_at: string|null}
     */
    private function taskSummary(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'priority' => $task->priority->value,
            'starts_at' => $task->starts_at?->toDateTimeString(),
            'ends_at' => $task->ends_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array{id: int, title: string, priority: string, starts_at: string|null, ends_at: string|null, effort_value: float, effort_unit: string}
     */
    private function taskEffortSummary(Task $task): array
    {
        return [
            ...$this->taskSummary($task),
            'effort_value' => (float) $task->effort_value,
            'effort_unit' => $task->effort_unit?->value ?? 'hours',
        ];
    }

    /**
     * @return array{id: int, name: string, utilization_percentage: float|null}
     */
    private function resourceSummary(Resource $resource, ?float $utilization): array
    {
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'utilization_percentage' => $utilization,
        ];
    }

    /**
     * @return array{id: int, task_id: int, task_title: string, task_priority: string, starts_at: string|null, ends_at: string|null, assignment_source: string}
     */
    private function assignmentSummary(TaskAssignment $assignment): array
    {
        $task = $assignment->task;

        return [
            'id' => $assignment->id,
            'task_id' => $assignment->task_id,
            'task_title' => $task?->title ?? 'Unknown task',
            'task_priority' => $task?->priority?->value ?? 'unknown',
            'starts_at' => ($assignment->starts_at ?? $task?->starts_at)?->toDateTimeString(),
            'ends_at' => ($assignment->ends_at ?? $task?->ends_at)?->toDateTimeString(),
            'assignment_source' => $assignment->assignment_source->value,
        ];
    }

    /**
     * @return array{assignment_id: int, task_id: int, task_title: string, task_priority: string, previous_starts_at: string|null, previous_ends_at: string|null, starts_at: string|null, ends_at: string|null}
     */
    private function rescheduledSummary(
        TaskAssignment $assignment,
        DateTimeInterface $previousStartsAt,
        DateTimeInterface $previousEndsAt,
        DateTimeInterface $startsAt,
        DateTimeInterface $endsAt,
    ): array {
        $task = $assignment->task;

        $taskPriority = $task?->priority?->value ?? TaskPriority::Low->value;

        return [
            'assignment_id' => $assignment->id,
            'task_id' => $assignment->task_id,
            'task_title' => $task?->title ?? 'Unknown task',
            'task_priority' => $taskPriority,
            'previous_starts_at' => $this->toCarbon($previousStartsAt)->toDateTimeString(),
            'previous_ends_at' => $this->toCarbon($previousEndsAt)->toDateTimeString(),
            'starts_at' => $this->toCarbon($startsAt)->toDateTimeString(),
            'ends_at' => $this->toCarbon($endsAt)->toDateTimeString(),
        ];
    }

    private function priorityRank(?TaskPriority $priority): int
    {
        return match ($priority) {
            TaskPriority::Urgent => 1,
            TaskPriority::High => 2,
            TaskPriority::Medium => 3,
            TaskPriority::Low => 4,
            null => 5,
        };
    }
}
