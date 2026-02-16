<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import { update } from '@/actions/App/Http/Controllers/MyAssignmentController';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { TaskAssignment } from '@/types/models';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    assignment: TaskAssignment;
    assigneeStatuses: EnumOption[];
}

const props = defineProps<Props>();

const form = useForm({
    assignee_status: props.assignment.assignee_status ?? '',
});

const recentlySuccessful = ref(false);
let successTimeout: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.assignment.assignee_status,
    (value) => {
        form.assignee_status = value ?? '';
    },
);

function submitStatus(value: string) {
    form.assignee_status = value;

    form.put(update(props.assignment.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            recentlySuccessful.value = true;

            if (successTimeout) {
                clearTimeout(successTimeout);
            }

            successTimeout = setTimeout(() => {
                recentlySuccessful.value = false;
            }, 2000);
        },
    });
}
</script>

<template>
    <div class="space-y-1.5">
        <Label
            :for="`status-${assignment.id}`"
            class="text-xs font-medium text-muted-foreground"
        >
            Ihr Status
        </Label>
        <div class="flex items-center gap-2">
            <Select
                :model-value="form.assignee_status"
                :disabled="form.processing"
                @update:model-value="submitStatus"
            >
                <SelectTrigger :id="`status-${assignment.id}`" class="flex-1">
                    <SelectValue placeholder="Status w\u00E4hlen" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="s in assigneeStatuses"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Spinner
                v-if="form.processing"
                class="size-4 shrink-0 text-muted-foreground"
            />
            <Check
                v-else-if="recentlySuccessful"
                class="size-4 shrink-0 text-green-600"
            />
        </div>
        <InputError :message="form.errors.assignee_status" />
    </div>
</template>
