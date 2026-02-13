<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import { store, update } from '@/actions/App/Http/Controllers/RoleController';
import FormDialog from '@/components/FormDialog.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Role } from '@/types/models';

interface Props {
    open: boolean;
    role?: Role | null;
}

const props = withDefaults(defineProps<Props>(), {
    role: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.role !== null;

const form = useForm({
    name: '',
    description: '',
});

watch(
    () => props.open,
    (open) => {
        if (open && props.role) {
            form.name = props.role.name;
            form.description = props.role.description ?? '';
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing() ? update(props.role!.id) : store();
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
        :title="isEditing() ? 'Rolle bearbeiten' : 'Rolle erstellen'"
        :description="
            isEditing()
                ? 'Ändern Sie die Eigenschaften dieser Rolle.'
                : 'Erstellen Sie eine neue Rolle.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="role-name"
                >Name <span class="text-destructive">*</span></Label
            >
            <Input
                id="role-name"
                v-model="form.name"
                placeholder="Name der Rolle"
                :disabled="form.processing"
                required
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="role-description">Beschreibung</Label>
            <Textarea
                id="role-description"
                v-model="form.description"
                placeholder="Optionale Beschreibung"
                :disabled="form.processing"
            />
            <InputError :message="form.errors.description" />
        </div>
    </FormDialog>
</template>
