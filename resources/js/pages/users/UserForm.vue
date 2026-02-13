<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import { store, update } from '@/actions/App/Http/Controllers/UserController';
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
import type { User } from '@/types';
import type { Role } from '@/types/models';

interface Props {
    open: boolean;
    user?: (User & { role_id?: number }) | null;
    roles: Pick<Role, 'id' | 'name'>[];
}

const props = withDefaults(defineProps<Props>(), {
    user: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isEditing = () => props.user !== null;

const form = useForm({
    name: '',
    email: '',
    password: '',
    role_id: null as number | null,
});

watch(
    () => props.open,
    (open) => {
        if (open && props.user) {
            form.name = props.user.name;
            form.email = props.user.email;
            form.password = '';
            form.role_id = (props.user.role_id as number) ?? null;
        } else if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    const action = isEditing() ? update(props.user!.id) : store();
    const method = isEditing() ? 'put' : 'post';

    const data = { ...form.data() };
    if (isEditing() && !data.password) {
        delete (data as Record<string, unknown>).password;
    }

    form.transform(() => data)[method](action.url, {
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
        :title="isEditing() ? 'Benutzer bearbeiten' : 'Benutzer erstellen'"
        :description="
            isEditing()
                ? 'Ändern Sie die Eigenschaften dieses Benutzers.'
                : 'Erstellen Sie einen neuen Benutzer.'
        "
        :processing="form.processing"
        @update:open="emit('update:open', $event)"
        @submit="submit"
    >
        <div class="grid gap-2">
            <Label for="user-name"
                >Name <span class="text-destructive">*</span></Label
            >
            <Input
                id="user-name"
                v-model="form.name"
                placeholder="Vollständiger Name"
                :disabled="form.processing"
                required
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="user-email"
                >E-Mail <span class="text-destructive">*</span></Label
            >
            <Input
                id="user-email"
                v-model="form.email"
                type="email"
                placeholder="benutzer@beispiel.de"
                :disabled="form.processing"
                required
            />
            <InputError :message="form.errors.email" />
        </div>

        <div class="grid gap-2">
            <Label for="user-password">
                Passwort
                <span v-if="!isEditing()" class="text-destructive">*</span>
            </Label>
            <Input
                id="user-password"
                v-model="form.password"
                type="password"
                :placeholder="
                    isEditing()
                        ? 'Leer lassen, um beizubehalten'
                        : 'Mindestens 8 Zeichen'
                "
                :disabled="form.processing"
                :required="!isEditing()"
            />
            <InputError :message="form.errors.password" />
        </div>

        <div class="grid gap-2">
            <Label for="user-role"
                >Rolle <span class="text-destructive">*</span></Label
            >
            <Select
                :model-value="form.role_id?.toString() ?? ''"
                :disabled="form.processing"
                @update:model-value="
                    form.role_id = $event ? Number($event) : null
                "
            >
                <SelectTrigger id="user-role">
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
    </FormDialog>
</template>
