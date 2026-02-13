<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/UserController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Role } from '@/types/models';

import UserForm from './UserForm.vue';

interface UserRow {
    id: number;
    name: string;
    email: string;
    role?: { id: number; name: string } | null;
    role_id?: number;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

interface Props {
    users: Paginated<UserRow>;
    roles: Pick<Role, 'id' | 'name'>[];
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Benutzer', href: index().url },
];

const columns: Column<UserRow>[] = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'E-Mail' },
    {
        key: 'role',
        label: 'Rolle',
        render: (row) => row.role?.name ?? '—',
    },
    {
        key: 'email_verified_at',
        label: 'Verifiziert',
        render: (row) => (row.email_verified_at ? 'Ja' : 'Nein'),
        class: 'w-28',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}

const formOpen = ref(false);
const editingUser = ref<UserRow | null>(null);

function openCreate() {
    editingUser.value = null;
    formOpen.value = true;
}

function openEdit(user: UserRow) {
    editingUser.value = user;
    formOpen.value = true;
}
</script>

<template>
    <Head title="Benutzer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Benutzer"
                description="Verwalten Sie die Benutzerkonten im System."
            />

            <DataTable
                :data="users"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/users"
                search-placeholder="Benutzer suchen..."
                empty-message="Keine Benutzer gefunden."
                delete-title="Benutzer löschen"
                delete-description="Möchten Sie diesen Benutzer wirklich löschen? Das Benutzerkonto wird unwiderruflich entfernt."
                dependency-delete-description="Dieser Benutzer hat eine zugeordnete Ressource, die unwiderruflich mitgelöscht wird."
                @edit="openEdit"
            >
                <template #toolbar>
                    <Button @click="openCreate">
                        <Plus class="mr-2 size-4" />
                        Benutzer erstellen
                    </Button>
                </template>
            </DataTable>
        </div>

        <UserForm
            :open="formOpen"
            :user="editingUser"
            :roles="roles"
            @update:open="formOpen = $event"
        />
    </AppLayout>
</template>
