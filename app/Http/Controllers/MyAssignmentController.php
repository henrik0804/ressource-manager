<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateTaskAssignmentAction;
use App\Enums\AssigneeStatus;
use App\Http\Requests\UpdateAssigneeStatusRequest;
use App\Models\TaskAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class MyAssignmentController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', TaskAssignment::class);

        $user = $request->user();

        $assignments = TaskAssignment::query()
            ->with(['task', 'resource'])
            ->whereHas('resource', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->paginate(15);

        $assigneeStatuses = collect(AssigneeStatus::cases())
            ->map(fn (AssigneeStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]);

        return Inertia::render('my-assignments/Index', [
            'assignments' => $assignments,
            'assigneeStatuses' => $assigneeStatuses,
        ]);
    }

    public function update(UpdateAssigneeStatusRequest $request, TaskAssignment $myAssignment, UpdateTaskAssignmentAction $action): RedirectResponse
    {
        $action->handle($myAssignment, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Status aktualisiert.',
        ]);
    }
}
