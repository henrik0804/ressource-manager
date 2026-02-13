<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteTaskRequirementAction;
use App\Actions\StoreTaskRequirementAction;
use App\Actions\UpdateTaskRequirementAction;
use App\Enums\QualificationLevel;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreTaskRequirementRequest;
use App\Http\Requests\UpdateTaskRequirementRequest;
use App\Models\Qualification;
use App\Models\Task;
use App\Models\TaskRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class TaskRequirementController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', TaskRequirement::class);

        $search = $request->string('search')->toString();

        $taskRequirements = TaskRequirement::query()
            ->with(['task', 'qualification'])
            ->when($search, fn ($query, $search) => $query
                ->whereHas('task', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                ->orWhereHas('qualification', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $tasks = Task::query()->orderBy('title')->get(['id', 'title']);
        $qualifications = Qualification::query()->orderBy('name')->get(['id', 'name']);
        $levels = collect(QualificationLevel::cases())
            ->map(fn (QualificationLevel $level) => [
                'value' => $level->value,
                'label' => $level->label(),
            ]);

        return Inertia::render('task-requirements/Index', [
            'taskRequirements' => $taskRequirements,
            'tasks' => $tasks,
            'qualifications' => $qualifications,
            'levels' => $levels,
            'search' => $search,
        ]);
    }

    public function store(StoreTaskRequirementRequest $request, StoreTaskRequirementAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task requirement created.',
        ]);
    }

    public function update(UpdateTaskRequirementRequest $request, TaskRequirement $taskRequirement, UpdateTaskRequirementAction $action): RedirectResponse
    {
        $action->handle($taskRequirement, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task requirement updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, TaskRequirement $taskRequirement, DeleteTaskRequirementAction $action): RedirectResponse
    {
        Gate::authorize('delete', $taskRequirement);

        $action->handle($taskRequirement);

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task requirement deleted.',
        ]);
    }
}
