<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/PermissionController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/permissions';
import type { BreadcrumbItem } from '@/types';
import type {
    AccessSection,
    Paginated,
    Permission,
    Role,
} from '@/types/models';

import PermissionForm from './PermissionForm.vue';

interface SectionOption {
    value: AccessSection;
    label: string;
}

interface Props {
    permissions: Paginated<Permission>;
    roles: Pick<Role, 'id' | 'name'>[];
    sections: SectionOption[];
    search: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Berechtigungen', href: index().url },
];

const sectionLabels = new Map(
    props.sections.map((section) => [section.value, section.label]),
);

function formatBoolean(value: boolean): string {
    return value ? 'Ja' : 'Nein';
}

const columns: Column<Permission>[] = [
    {
        key: 'role',
        label: 'Rolle',
        render: (row) => row.role?.name ?? '—',
    },
    {
        key: 'section',
        label: 'Bereich',
        render: (row) => sectionLabels.get(row.section) ?? row.section,
    },
    {
        key: 'can_read',
        label: 'Lesen',
        render: (row) => formatBoolean(row.can_read),
        class: 'w-28',
    },
    {
        key: 'can_write',
        label: 'Schreiben',
        render: (row) => formatBoolean(row.can_write),
        class: 'w-28',
    },
    {
        key: 'can_write_owned',
        label: 'Eigene ändern',
        render: (row) => formatBoolean(row.can_write_owned),
        class: 'w-36',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}

const formOpen = ref(false);
const editingPermission = ref<Permission | null>(null);

function openCreate() {
    editingPermission.value = null;
    formOpen.value = true;
}

function openEdit(permission: Permission) {
    editingPermission.value = permission;
    formOpen.value = true;
}
</script>

<template>
    <Head title="Berechtigungen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Berechtigungen"
                description="Verwalten Sie die Zugriffsrechte je Rolle."
            />

            <DataTable
                :data="permissions"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/permissions"
                search-placeholder="Berechtigungen suchen..."
                empty-message="Keine Berechtigungen gefunden."
                delete-title="Berechtigung löschen"
                delete-description="Möchten Sie diese Berechtigung wirklich löschen?"
                @edit="openEdit"
            >
                <template #toolbar>
                    <Button @click="openCreate">
                        <Plus class="mr-2 size-4" />
                        Berechtigung erstellen
                    </Button>
                </template>
            </DataTable>
        </div>

        <PermissionForm
            :open="formOpen"
            :permission="editingPermission"
            :roles="roles"
            :sections="sections"
            @update:open="formOpen = $event"
        />
    </AppLayout>
</template>
