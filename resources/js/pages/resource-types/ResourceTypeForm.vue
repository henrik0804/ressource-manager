<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/ResourceTypeController';
import FormDialog from '@/components/FormDialog.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { ResourceType } from '@/types/models';

interface Props {
    open: boolean;
    resourceType?: ResourceType | null;
}

const props = withDefaults(defineProps<Props>(), {
    resourceType: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.resourceType !== null;

const form = useForm({
    name: '',
    description: '',
});

watch(
    () => props.open,
    (open) => {
        if (open && props.resourceType) {
            form.name = props.resourceType.name;
            form.description = props.resourceType.description ?? '';
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing() ? update(props.resourceType!.id) : store();
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
            isEditing() ? 'Ressourcentyp bearbeiten' : 'Ressourcentyp erstellen'
        "
        :description="
            isEditing()
                ? 'Ändern Sie die Eigenschaften dieses Ressourcentyps.'
                : 'Erstellen Sie einen neuen Ressourcentyp.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="resource-type-name"
                >Name <span class="text-destructive">*</span></Label
            >
            <Input
                id="resource-type-name"
                v-model="form.name"
                placeholder="Name des Ressourcentyps"
                :disabled="form.processing"
                required
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="resource-type-description">Beschreibung</Label>
            <Textarea
                id="resource-type-description"
                v-model="form.description"
                placeholder="Optionale Beschreibung"
                :disabled="form.processing"
            />
            <InputError :message="form.errors.description" />
        </div>
    </FormDialog>
</template>
