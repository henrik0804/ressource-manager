<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/ResourceQualificationController';
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
import type {
    Qualification,
    Resource,
    ResourceQualification,
} from '@/types/models';

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    open: boolean;
    resourceQualification?: ResourceQualification | null;
    resources: Pick<Resource, 'id' | 'name'>[];
    qualifications: Pick<Qualification, 'id' | 'name'>[];
    levels: EnumOption[];
}

const props = withDefaults(defineProps<Props>(), {
    resourceQualification: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.resourceQualification !== null;

const form = useForm({
    resource_id: null as number | null,
    qualification_id: null as number | null,
    level: '' as string,
});

watch(
    () => props.open,
    (open) => {
        if (open && props.resourceQualification) {
            form.resource_id = props.resourceQualification.resource_id;
            form.qualification_id =
                props.resourceQualification.qualification_id;
            form.level = props.resourceQualification.level ?? '';
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing()
        ? update(props.resourceQualification!.id)
        : store();
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
            isEditing()
                ? 'Ressourcenqualifikation bearbeiten'
                : 'Ressourcenqualifikation erstellen'
        "
        :description="
            isEditing()
                ? 'Ändern Sie die Zuordnung.'
                : 'Ordnen Sie eine Qualifikation zu.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="rq-resource"
                >Ressource <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.resource_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.resource_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="rq-resource">
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

        <div class="grid gap-2">
            <Label for="rq-qualification"
                >Qualifikation <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.qualification_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.qualification_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="rq-qualification">
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
            <Label for="rq-level">Stufe</Label>
            <Select
                :model-value="form.level"
                :disabled="form.processing"
                @update:model-value="form.level = $event"
            >
                <SelectTrigger id="rq-level">
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
            <InputError :message="form.errors.level" />
        </div>
    </FormDialog>
</template>
