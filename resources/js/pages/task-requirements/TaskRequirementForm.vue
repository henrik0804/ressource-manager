<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/TaskRequirementController';
import FormDialog from '@/components/FormDialog.vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Qualification, Task, TaskRequirement } from '@/types/models';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    open: boolean;
    taskRequirement?: TaskRequirement | null;
    tasks: Pick<Task, 'id' | 'title'>[];
    qualifications: Pick<Qualification, 'id' | 'name'>[];
    levels: EnumOption[];
}

const props = withDefaults(defineProps<Props>(), {
    taskRequirement: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.taskRequirement !== null;

const form = useForm({
    task_id: null as number | null,
    qualification_id: null as number | null,
    required_level: '' as string,
});

watch(
    () => props.open,
    (open) => {
        if (open && props.taskRequirement) {
            form.task_id = props.taskRequirement.task_id;
            form.qualification_id = props.taskRequirement.qualification_id;
            form.required_level = props.taskRequirement.required_level ?? '';
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing() ? update(props.taskRequirement!.id) : store();
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
        :title="
            isEditing() ? 'Anforderung bearbeiten' : 'Anforderung erstellen'
        "
        :description="
            isEditing()
                ? 'Ändern Sie die Aufgabenanforderung.'
                : 'Erstellen Sie eine neue Aufgabenanforderung.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="task-req-task"
                >Aufgabe <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.task_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.task_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="task-req-task">
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
            <Label for="task-req-qualification"
                >Qualifikation <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.qualification_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.qualification_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="task-req-qualification">
                    <SelectValue placeholder="Qualifikation wählen" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="q in qualifications"
                        :key="q.id"
                        :value="q.id.toString()"
                    >
                        {{ q.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.qualification_id" />
        </div>

        <div class="grid gap-2">
            <Label for="task-req-level">Erforderliche Stufe</Label>
            <Select
                :model-value="form.required_level"
                :disabled="form.processing"
                @update:model-value="form.required_level = $event"
            >
                <SelectTrigger id="task-req-level">
                    <SelectValue placeholder="Keine Angabe" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="level in levels"
                        :key="level.value"
                        :value="level.value"
                    >
                        {{ level.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.required_level" />
        </div>
    </FormDialog>
</template>
