<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccessSection;
use App\Enums\AssigneeStatus;
use App\Models\TaskAssignment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $myAssignments = null;
        $statusSummary = null;

        if ($user && $user->canReadSection(AccessSection::EmployeeFeedback)) {
            $myAssignments = TaskAssignment::query()
                ->with(['task', 'resource'])
                ->whereHas('resource', fn ($query) => $query->where('user_id', $user->id))
                ->latest()
                ->take(5)
                ->get();

            $statusSummary = TaskAssignment::query()
                ->whereHas('resource', fn ($query) => $query->where('user_id', $user->id))
                ->selectRaw('assignee_status, count(*) as count')
                ->groupBy('assignee_status')
                ->pluck('count', 'assignee_status')
                ->toArray();
        }

        $assigneeStatuses = collect(AssigneeStatus::cases())
            ->map(fn (AssigneeStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]);

        return Inertia::render('Dashboard', [
            'myAssignments' => $myAssignments,
            'statusSummary' => $statusSummary,
            'assigneeStatuses' => $assigneeStatuses,
        ]);
    }
}
