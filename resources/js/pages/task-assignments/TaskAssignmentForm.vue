<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/TaskAssignmentController';
import FormDialog from '@/components/FormDialog.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Resource, Task, TaskAssignment } from '@/types/models';

interface Props {
    open: boolean;
    taskAssignment?: TaskAssignment | null;
    tasks: Pick<Task, 'id' | 'title'>[];
    resources: Pick<Resource, 'id' | 'name'>[];
}

const props = withDefaults(defineProps<Props>(), {
    taskAssignment: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.taskAssignment !== null;

function formatDateForInput(dateString: string | null): string {
    if (!dateString) {
        return '';
    }
    return dateString.substring(0, 10);
}

const form = useForm({
    task_id: null as number | null,
    resource_id: null as number | null,
    starts_at: '',
    ends_at: '',
    allocation_ratio: '' as string | number,
    assignment_source: '',
    assignee_status: '',
});

watch(
    () => props.open,
    (open) => {
        if (open && props.taskAssignment) {
            form.task_id = props.taskAssignment.task_id;
            form.resource_id = props.taskAssignment.resource_id;
            form.starts_at = formatDateForInput(props.taskAssignment.starts_at);
            form.ends_at = formatDateForInput(props.taskAssignment.ends_at);
            form.allocation_ratio = props.taskAssignment.allocation_ratio ?? '';
            form.assignment_source = props.taskAssignment.assignment_source;
            form.assignee_status = props.taskAssignment.assignee_status ?? '';
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing() ? update(props.taskAssignment!.id) : store();
    const method = isEditing() ? 'put' : 'post';

    form[method](action.url, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
            form.reset();
        },
    });
}
</script>

<template>
    <FormDialog
        :open="open"
        :title="isEditing() ? 'Zuweisung bearbeiten' : 'Zuweisung erstellen'"
        :description="
            isEditing()
                ? 'Ändern Sie die Aufgabenzuweisung.'
                : 'Erstellen Sie eine neue Aufgabenzuweisung.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="assignment-task"
                >Aufgabe <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.task_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.task_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="assignment-task">
                    <SelectValue placeholder="Aufgabe wählen" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="task in tasks"
                        :key="task.id"
                        :value="task.id.toString()"
                    >
                        {{ task.title }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.task_id" />
        </div>

        <div class="grid gap-2">
            <Label for="assignment-resource"
                >Ressource <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.resource_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.resource_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="assignment-resource">
                    <SelectValue placeholder="Ressource wählen" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="resource in resources"
                        :key="resource.id"
                        :value="resource.id.toString()"
                    >
                        {{ resource.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.resource_id" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="assignment-starts-at">Beginn</Label>
                <Input
                    id="assignment-starts-at"
                    v-model="form.starts_at"
                    type="date"
                    :disabled="form.processing"
                />
                <InputError :message="form.errors.starts_at" />
            </div>

            <div class="grid gap-2">
                <Label for="assignment-ends-at">Ende</Label>
                <Input
                    id="assignment-ends-at"
                    v-model="form.ends_at"
                    type="date"
                    :disabled="form.processing"
                />
                <InputError :message="form.errors.ends_at" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="assignment-allocation">Auslastung</Label>
            <Input
                id="assignment-allocation"
                v-model="form.allocation_ratio"
                type="number"
                min="0"
                max="1"
                step="0.01"
                placeholder="z.B. 0.5 für 50%"
                :disabled="form.processing"
            />
            <InputError :message="form.errors.allocation_ratio" />
        </div>

        <div class="grid gap-2">
            <Label for="assignment-source"
                >Quelle <span class="text-destructive">*</span></Label
            >
            <Input
                id="assignment-source"
                v-model="form.assignment_source"
                placeholder="z.B. Manuell, Automatisch"
                :disabled="form.processing"
                required
            />
            <InputError :message="form.errors.assignment_source" />
        </div>

        <div class="grid gap-2">
            <Label for="assignment-status">Status</Label>
            <Input
                id="assignment-status"
                v-model="form.assignee_status"
                placeholder="Optionaler Status"
                :disabled="form.processing"
            />
            <InputError :message="form.errors.assignee_status" />
        </div>
    </FormDialog>
</template>
