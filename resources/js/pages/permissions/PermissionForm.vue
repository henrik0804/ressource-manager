<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/PermissionController';
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
import { Switch } from '@/components/ui/switch';
import type { Permission, Role } from '@/types/models';

interface SectionOption {
    value: string;
    label: string;
}

interface Props {
    open: boolean;
    permission?: Permission | null;
    roles: Pick<Role, 'id' | 'name'>[];
    sections: SectionOption[];
}

const props = withDefaults(defineProps<Props>(), {
    permission: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.permission !== null;

const form = useForm({
    role_id: null as number | null,
    section: '' as string,
    can_read: false,
    can_write: false,
    can_write_owned: false,
});

watch(
    () => props.open,
    (open) => {
        if (open && props.permission) {
            form.role_id = props.permission.role_id;
            form.section = props.permission.section;
            form.can_read = props.permission.can_read;
            form.can_write = props.permission.can_write;
            form.can_write_owned = props.permission.can_write_owned;
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing() ? update(props.permission!.id) : store();
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
            isEditing() ? 'Berechtigung bearbeiten' : 'Berechtigung erstellen'
        "
        :description="
            isEditing()
                ? 'Ändern Sie die Zugriffsrechte.'
                : 'Erstellen Sie eine neue Berechtigung.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="permission-role"
                >Rolle <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.role_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.role_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="permission-role">
                    <SelectValue placeholder="Rolle wählen" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="role in roles"
                        :key="role.id"
                        :value="role.id.toString()"
                    >
                        {{ role.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.role_id" />
        </div>

        <div class="grid gap-2">
            <Label for="permission-section"
                >Bereich <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.section"
                :disabled="form.processing"
                @update:model-value="form.section = $event"
            >
                <SelectTrigger id="permission-section">
                    <SelectValue placeholder="Bereich wählen" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="section in sections"
                        :key="section.value"
                        :value="section.value"
                    >
                        {{ section.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.section" />
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <Label for="permission-can-read">Lesen</Label>
                <Switch
                    id="permission-can-read"
                    :checked="form.can_read"
                    :disabled="form.processing"
                    @update:checked="form.can_read = $event"
                />
            </div>
            <InputError :message="form.errors.can_read" />

            <div class="flex items-center justify-between">
                <Label for="permission-can-write">Schreiben</Label>
                <Switch
                    id="permission-can-write"
                    :checked="form.can_write"
                    :disabled="form.processing"
                    @update:checked="form.can_write = $event"
                />
            </div>
            <InputError :message="form.errors.can_write" />

            <div class="flex items-center justify-between">
                <Label for="permission-can-write-owned">Eigene ändern</Label>
                <Switch
                    id="permission-can-write-owned"
                    :checked="form.can_write_owned"
                    :disabled="form.processing"
                    @update:checked="form.can_write_owned = $event"
                />
            </div>
            <InputError :message="form.errors.can_write_owned" />
        </div>
    </FormDialog>
</template>
