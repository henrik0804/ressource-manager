<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/RoleController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Role } from '@/types/models';

import RoleForm from './RoleForm.vue';

interface Props {
    roles: Paginated<Role>;
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Rollen', href: index().url }];

const columns: Column<Role>[] = [
    { key: 'name', label: 'Name' },
    { key: 'description', label: 'Beschreibung' },
    {
        key: 'users_count',
        label: 'Benutzer',
        render: (row) => String(row.users_count ?? 0),
        class: 'w-32',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}

const formOpen = ref(false);
const editingRole = ref<Role | null>(null);

function openCreate() {
    editingRole.value = null;
    formOpen.value = true;
}

function openEdit(role: Role) {
    editingRole.value = role;
    formOpen.value = true;
}
</script>

<template>
    <Head title="Rollen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Rollen"
                description="Verwalten Sie die Benutzerrollen im System."
            />

            <DataTable
                :data="roles"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/roles"
                search-placeholder="Rollen suchen..."
                empty-message="Keine Rollen gefunden."
                delete-title="Rolle löschen"
                delete-description="Möchten Sie diese Rolle wirklich löschen?"
                dependency-delete-description="Diese Rolle hat zugewiesene Benutzer, die unwiderruflich mitgelöscht werden."
                @edit="openEdit"
            >
                <template #toolbar>
                    <Button @click="openCreate">
                        <Plus class="mr-2 size-4" />
                        Rolle erstellen
                    </Button>
                </template>
            </DataTable>
        </div>

        <RoleForm
            :open="formOpen"
            :role="editingRole"
            @update:open="formOpen = $event"
        />
    </AppLayout>
</template>
