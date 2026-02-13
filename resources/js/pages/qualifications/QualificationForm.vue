<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/QualificationController';
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
import { Textarea } from '@/components/ui/textarea';
import type { Qualification, ResourceType } from '@/types/models';

interface Props {
    open: boolean;
    qualification?: Qualification | null;
    resourceTypes: Pick<ResourceType, 'id' | 'name'>[];
}

const props = withDefaults(defineProps<Props>(), {
    qualification: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.qualification !== null;

const form = useForm({
    name: '',
    description: '',
    resource_type_id: null as number | null,
});

watch(
    () => props.open,
    (open) => {
        if (open && props.qualification) {
            form.name = props.qualification.name;
            form.description = props.qualification.description ?? '';
            form.resource_type_id = props.qualification.resource_type_id;
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing() ? update(props.qualification!.id) : store();
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
            isEditing() ? 'Qualifikation bearbeiten' : 'Qualifikation erstellen'
        "
        :description="
            isEditing()
                ? 'Ändern Sie die Eigenschaften dieser Qualifikation.'
                : 'Erstellen Sie eine neue Qualifikation.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="qualification-name"
                >Name <span class="text-destructive">*</span></Label
            >
            <Input
                id="qualification-name"
                v-model="form.name"
                placeholder="Name der Qualifikation"
                :disabled="form.processing"
                required
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="qualification-description">Beschreibung</Label>
            <Textarea
                id="qualification-description"
                v-model="form.description"
                placeholder="Optionale Beschreibung"
                :disabled="form.processing"
            />
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-2">
            <Label for="qualification-resource-type">Ressourcentyp</Label>
            <Select
                :model-value="form.resource_type_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.resource_type_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="qualification-resource-type">
                    <SelectValue placeholder="Keiner zugeordnet" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="rt in resourceTypes"
                        :key="rt.id"
                        :value="rt.id.toString()"
                    >
                        {{ rt.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.resource_type_id" />
        </div>
    </FormDialog>
</template>
