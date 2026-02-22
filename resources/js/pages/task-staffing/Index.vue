<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    Pencil,
    Plus,
    Search,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/TaskAssignmentController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { taskStaffing } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type {
    AssigneeStatus,
    AssignmentSource,
    EffortUnit,
    Resource,
    Task,
    TaskAssignment,
    TaskPriority,
    TaskStatus,
} from '@/types/models';

import TaskAssignmentForm from '../task-assignments/TaskAssignmentForm.vue';

interface EnumOption {
    value: string;
    label: string;
}

interface StaffingAssignment {
    id: number;
    task_id: number;
    resource_id: number;
    resource_name: string | null;
    resource_capacity_value: string | null;
    resource_capacity_unit: string | null;
    allocation_ratio: string | null;
    starts_at: string | null;
    ends_at: string | null;
    assignment_source: AssignmentSource;
    assignee_status: AssigneeStatus | null;
    contributed_hours: number;
}

interface StaffingRequirement {
    id: number;
    qualification_name: string | null;
    required_level: string | null;
}

interface StaffingTask {
    task: {
        id: number;
        title: string;
        description: string | null;
        starts_at: string;
        ends_at: string;
        effort_value: string;
        effort_unit: EffortUnit;
        priority: TaskPriority;
        status: TaskStatus;
    };
    effort_hours: number;
    assigned_capacity_hours: number;
    coverage_percentage: number;
    assignments: StaffingAssignment[];
    requirements: StaffingRequirement[];
}

interface Props {
    tasks: StaffingTask[];
    search: string;
    canWrite: boolean;
    allTasks?: Pick<Task, 'id' | 'title'>[];
    resources?: Pick<
        Resource,
        'id' | 'name' | 'capacity_unit' | 'capacity_value'
    >[];
    assignmentSources?: EnumOption[];
    assigneeStatuses?: EnumOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Aufgabenbesetzung', href: taskStaffing().url },
];

// Search
const searchQuery = ref(props.search);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(searchQuery, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            taskStaffing({ query: { search: value || undefined } }).url,
            {},
            { preserveState: true, preserveScroll: true },
        );
    }, 300);
});

// CRUD state
const formOpen = ref(false);
const editingAssignment = ref<TaskAssignment | null>(null);
const prefilledTaskId = ref<number | null>(null);

const deleteDialogOpen = ref(false);
const deletingAssignment = ref<StaffingAssignment | null>(null);
const isDeleting = ref(false);

function openCreateForTask(taskId: number) {
    // Build a stub that the form interprets as "create" but with task_id prefilled.
    // The form uses `isEditing = () => props.taskAssignment !== null` — passing a
    // stub with id 0 causes it to POST (store) rather than PUT (update) because
    // `isEditing()` is based on the prop being passed from outside. We'll pass null
    // and rely on the form's reset + manual override via watch.
    editingAssignment.value = null;
    prefilledTaskId.value = taskId;
    formOpen.value = true;
}

function openEdit(assignment: StaffingAssignment) {
    editingAssignment.value = {
        id: assignment.id,
        task_id: assignment.task_id,
        resource_id: assignment.resource_id,
        starts_at: assignment.starts_at,
        ends_at: assignment.ends_at,
        allocation_ratio: assignment.allocation_ratio,
        assignment_source: assignment.assignment_source,
        assignee_status: assignment.assignee_status,
        created_at: '',
        updated_at: '',
    } as TaskAssignment;
    prefilledTaskId.value = null;
    formOpen.value = true;
}

function confirmDelete(assignment: StaffingAssignment) {
    deletingAssignment.value = assignment;
    deleteDialogOpen.value = true;
}

function executeDelete() {
    if (!deletingAssignment.value) {
        return;
    }

    isDeleting.value = true;

    router.delete(destroy(deletingAssignment.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleting.value = false;
            deleteDialogOpen.value = false;
            deletingAssignment.value = null;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
}

const activeDefaultTaskId = computed(() =>
    editingAssignment.value ? null : prefilledTaskId.value,
);

// Summary
const summaryStats = computed(() => {
    const total = props.tasks.length;
    const understaffed = props.tasks.filter(
        (t) => t.effort_hours > 0 && t.coverage_percentage < 100,
    ).length;
    const fullyStaffed = props.tasks.filter(
        (t) => t.effort_hours > 0 && t.coverage_percentage >= 100,
    ).length;
    const unassigned = props.tasks.filter(
        (t) => t.assignments.length === 0,
    ).length;

    return { total, understaffed, fullyStaffed, unassigned };
});

// Labels
const priorityLabels: Record<TaskPriority, string> = {
    urgent: 'Dringend',
    high: 'Hoch',
    medium: 'Mittel',
    low: 'Niedrig',
};

const statusLabels: Record<TaskStatus, string> = {
    planned: 'Geplant',
    in_progress: 'In Bearbeitung',
    blocked: 'Blockiert',
    done: 'Erledigt',
};

const effortUnitLabels: Record<EffortUnit, string> = {
    hours: 'Std.',
    days: 'Tage',
};

const assigneeStatusLabels: Record<AssigneeStatus, string> = {
    pending: 'Ausstehend',
    accepted: 'Akzeptiert',
    in_progress: 'In Bearbeitung',
    done: 'Erledigt',
    rejected: 'Abgelehnt',
};

const qualificationLevelLabels: Record<string, string> = {
    beginner: 'Anfänger',
    intermediate: 'Fortgeschritten',
    advanced: 'Erfahren',
    expert: 'Experte',
};

function priorityVariant(
    priority: TaskPriority,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    switch (priority) {
        case 'urgent':
            return 'destructive';
        case 'high':
            return 'default';
        case 'medium':
            return 'secondary';
        default:
            return 'outline';
    }
}

function statusVariant(
    status: TaskStatus,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    switch (status) {
        case 'in_progress':
            return 'default';
        case 'blocked':
            return 'destructive';
        case 'done':
            return 'secondary';
        default:
            return 'outline';
    }
}

function coverageColor(percentage: number): string {
    if (percentage >= 100) {
        return 'bg-emerald-500 dark:bg-emerald-600';
    }

    if (percentage >= 50) {
        return 'bg-amber-500 dark:bg-amber-500';
    }

    if (percentage > 0) {
        return 'bg-red-500 dark:bg-red-600';
    }

    return 'bg-muted';
}

function coverageTextColor(percentage: number): string {
    if (percentage >= 100) {
        return 'text-emerald-600 dark:text-emerald-400';
    }

    if (percentage >= 50) {
        return 'text-amber-600 dark:text-amber-400';
    }

    return 'text-red-600 dark:text-red-400';
}

function formatNumber(value: number): string {
    return value % 1 === 0 ? value.toString() : value.toFixed(1);
}

function formatDateTime(dateString: string | null): string {
    if (!dateString) {
        return '\u2014';
    }

    const normalized = dateString.includes('T')
        ? dateString
        : dateString.replace(' ', 'T');
    const parsed = new Date(normalized);

    if (Number.isNaN(parsed.getTime())) {
        return '\u2014';
    }

    return parsed.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Aufgabenbesetzung" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Aufgabenbesetzung"
                description="Aufwand, zugewiesene Ressourcen und Besetzungsgrad pro Aufgabe im Überblick."
            />

            <!-- Summary cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Aufgaben
                        </CardTitle>
                        <Clock class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ summaryStats.total }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Voll besetzt
                        </CardTitle>
                        <CheckCircle2 class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"
                        >
                            {{ summaryStats.fullyStaffed }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Unterbesetzt
                        </CardTitle>
                        <AlertTriangle class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold"
                            :class="
                                summaryStats.understaffed > 0
                                    ? 'text-amber-600 dark:text-amber-400'
                                    : ''
                            "
                        >
                            {{ summaryStats.understaffed }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Ohne Zuweisung
                        </CardTitle>
                        <Users class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold"
                            :class="
                                summaryStats.unassigned > 0
                                    ? 'text-red-600 dark:text-red-400'
                                    : ''
                            "
                        >
                            {{ summaryStats.unassigned }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Search -->
            <div class="relative max-w-sm">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Aufgaben suchen..."
                    class="pl-9"
                />
            </div>

            <!-- Task staffing cards -->
            <div v-if="tasks.length > 0" class="space-y-4">
                <Card v-for="item in tasks" :key="item.task.id">
                    <CardHeader class="pb-3">
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div class="space-y-1">
                                <CardTitle class="text-base">
                                    {{ item.task.title }}
                                </CardTitle>
                                <CardDescription
                                    v-if="item.task.description"
                                    class="line-clamp-1"
                                >
                                    {{ item.task.description }}
                                </CardDescription>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <Badge
                                    :variant="
                                        priorityVariant(item.task.priority)
                                    "
                                >
                                    {{ priorityLabels[item.task.priority] }}
                                </Badge>
                                <Badge
                                    :variant="statusVariant(item.task.status)"
                                >
                                    {{ statusLabels[item.task.status] }}
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Effort and coverage row -->
                        <div
                            class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm"
                        >
                            <div class="text-muted-foreground">
                                <span class="font-medium text-foreground">
                                    {{ item.task.effort_value }}
                                    {{
                                        effortUnitLabels[item.task.effort_unit]
                                    }}
                                </span>
                                Aufwand
                                <span
                                    v-if="
                                        item.task.effort_unit === 'days' &&
                                        item.effort_hours > 0
                                    "
                                    class="text-xs"
                                >
                                    ({{ formatNumber(item.effort_hours) }} Std.)
                                </span>
                            </div>
                            <div class="text-muted-foreground">
                                {{ formatDateTime(item.task.starts_at) }}
                                &ndash;
                                {{ formatDateTime(item.task.ends_at) }}
                            </div>
                            <div
                                v-if="item.requirements.length > 0"
                                class="text-muted-foreground"
                            >
                                Anforderungen:
                                <span
                                    v-for="(req, idx) in item.requirements"
                                    :key="req.id"
                                >
                                    {{ req.qualification_name
                                    }}<template v-if="req.required_level">
                                        ({{
                                            qualificationLevelLabels[
                                                req.required_level
                                            ] ?? req.required_level
                                        }})</template
                                    ><template
                                        v-if="
                                            idx < item.requirements.length - 1
                                        "
                                        >,
                                    </template>
                                </span>
                            </div>
                        </div>

                        <!-- Coverage bar -->
                        <TooltipProvider :delay-duration="100">
                            <div class="space-y-1.5">
                                <div
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="text-muted-foreground">
                                        Besetzungsgrad
                                    </span>
                                    <span
                                        class="font-semibold tabular-nums"
                                        :class="
                                            item.effort_hours > 0
                                                ? coverageTextColor(
                                                      item.coverage_percentage,
                                                  )
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        <template v-if="item.effort_hours > 0">
                                            {{
                                                formatNumber(
                                                    item.coverage_percentage,
                                                )
                                            }}%
                                        </template>
                                        <template v-else>&mdash;</template>
                                    </span>
                                </div>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <div
                                            class="h-2.5 w-full overflow-hidden rounded-full bg-muted"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="
                                                    coverageColor(
                                                        item.coverage_percentage,
                                                    )
                                                "
                                                :style="{
                                                    width: `${Math.min(item.coverage_percentage, 100)}%`,
                                                }"
                                            />
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        side="bottom"
                                        class="text-xs"
                                    >
                                        <div class="space-y-1">
                                            <div>
                                                Aufwand:
                                                {{
                                                    formatNumber(
                                                        item.effort_hours,
                                                    )
                                                }}
                                                Std.
                                            </div>
                                            <div>
                                                Zugewiesen:
                                                {{
                                                    formatNumber(
                                                        item.assigned_capacity_hours,
                                                    )
                                                }}
                                                Std.
                                            </div>
                                            <div>
                                                {{ item.assignments.length }}
                                                Ressource(n) zugewiesen
                                            </div>
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </TooltipProvider>

                        <!-- Assigned resources table -->
                        <div
                            v-if="item.assignments.length > 0"
                            class="rounded-md border"
                        >
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Ressource</TableHead>
                                        <TableHead>Zeitraum</TableHead>
                                        <TableHead class="text-right">
                                            Anteil
                                        </TableHead>
                                        <TableHead class="text-right">
                                            Beitrag
                                        </TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead v-if="canWrite" class="w-20">
                                            <span class="sr-only"
                                                >Aktionen</span
                                            >
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="assignment in item.assignments"
                                        :key="assignment.id"
                                    >
                                        <TableCell class="font-medium">
                                            {{
                                                assignment.resource_name ??
                                                '\u2014'
                                            }}
                                        </TableCell>
                                        <TableCell
                                            class="text-muted-foreground"
                                        >
                                            {{
                                                formatDateTime(
                                                    assignment.starts_at,
                                                )
                                            }}
                                            &ndash;
                                            {{
                                                formatDateTime(
                                                    assignment.ends_at,
                                                )
                                            }}
                                        </TableCell>
                                        <TableCell
                                            class="text-right tabular-nums"
                                        >
                                            <template
                                                v-if="
                                                    assignment.allocation_ratio !==
                                                    null
                                                "
                                            >
                                                {{
                                                    formatNumber(
                                                        Number(
                                                            assignment.allocation_ratio,
                                                        ) * 100,
                                                    )
                                                }}%
                                            </template>
                                            <template v-else>100%</template>
                                        </TableCell>
                                        <TableCell
                                            class="text-right tabular-nums"
                                        >
                                            {{
                                                formatNumber(
                                                    assignment.contributed_hours,
                                                )
                                            }}
                                            Std.
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                v-if="
                                                    assignment.assignee_status
                                                "
                                                variant="outline"
                                                class="text-xs"
                                            >
                                                {{
                                                    assigneeStatusLabels[
                                                        assignment
                                                            .assignee_status
                                                    ] ??
                                                    assignment.assignee_status
                                                }}
                                            </Badge>
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                                >&mdash;</span
                                            >
                                        </TableCell>
                                        <TableCell v-if="canWrite" class="w-20">
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="size-7"
                                                    @click="
                                                        openEdit(assignment)
                                                    "
                                                >
                                                    <Pencil class="size-3.5" />
                                                    <span class="sr-only"
                                                        >Bearbeiten</span
                                                    >
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="size-7 text-destructive hover:text-destructive"
                                                    @click="
                                                        confirmDelete(
                                                            assignment,
                                                        )
                                                    "
                                                >
                                                    <Trash2 class="size-3.5" />
                                                    <span class="sr-only"
                                                        >Löschen</span
                                                    >
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Empty + add button -->
                        <div
                            v-if="item.assignments.length === 0 && !canWrite"
                            class="rounded-md border border-dashed p-3 text-center text-sm text-muted-foreground"
                        >
                            Keine Ressourcen zugewiesen.
                        </div>

                        <div
                            v-if="canWrite"
                            class="flex items-center"
                            :class="
                                item.assignments.length === 0
                                    ? 'justify-center rounded-md border border-dashed p-3'
                                    : ''
                            "
                        >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-muted-foreground"
                                @click="openCreateForTask(item.task.id)"
                            >
                                <Plus class="mr-1 size-4" />
                                Ressource zuweisen
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty state -->
            <div
                v-else
                class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-sidebar-border/70 p-8 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">
                    Keine Aufgaben gefunden.
                </p>
            </div>
        </div>

        <!-- Assignment form dialog (reused from task-assignments) -->
        <TaskAssignmentForm
            v-if="canWrite"
            :open="formOpen"
            :task-assignment="editingAssignment"
            :default-task-id="activeDefaultTaskId"
            :tasks="allTasks ?? []"
            :resources="resources ?? []"
            :assignment-sources="assignmentSources ?? []"
            :assignee-statuses="assigneeStatuses ?? []"
            @update:open="formOpen = $event"
        />

        <!-- Delete confirmation -->
        <ConfirmDialog
            :open="deleteDialogOpen"
            title="Zuweisung löschen"
            description="Möchten Sie diese Zuweisung wirklich löschen?"
            confirm-label="Löschen"
            :processing="isDeleting"
            @update:open="deleteDialogOpen = $event"
            @confirm="executeDelete"
        />
    </AppLayout>
</template>
