<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/ResourceTypeController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/resource-types';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, ResourceType } from '@/types/models';

import ResourceTypeForm from './ResourceTypeForm.vue';

interface Props {
    resourceTypes: Paginated<ResourceType>;
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ressourcentypen', href: index().url },
];

const columns: Column<ResourceType>[] = [
    { key: 'name', label: 'Name' },
    { key: 'description', label: 'Beschreibung' },
    {
        key: 'resources_count',
        label: 'Ressourcen',
        render: (row) => String(row.resources_count ?? 0),
        class: 'w-32',
    },
    {
        key: 'qualifications_count',
        label: 'Qualifikationen',
        render: (row) => String(row.qualifications_count ?? 0),
        class: 'w-40',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}

const formOpen = ref(false);
const editingResourceType = ref<ResourceType | null>(null);

function openCreate() {
    editingResourceType.value = null;
    formOpen.value = true;
}

function openEdit(resourceType: ResourceType) {
    editingResourceType.value = resourceType;
    formOpen.value = true;
}
</script>

<template>
    <Head title="Ressourcentypen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Ressourcentypen"
                description="Verwalten Sie die verschiedenen Typen von Ressourcen."
            />

            <DataTable
                :data="resourceTypes"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/resource-types"
                search-placeholder="Ressourcentypen suchen..."
                empty-message="Keine Ressourcentypen gefunden."
                delete-title="Ressourcentyp löschen"
                delete-description="Möchten Sie diesen Ressourcentyp wirklich löschen?"
                dependency-delete-description="Dieser Ressourcentyp hat abhängige Daten, die unwiderruflich mitgelöscht werden."
                @edit="openEdit"
            >
                <template #toolbar>
                    <Button @click="openCreate">
                        <Plus class="mr-2 size-4" />
                        Ressourcentyp erstellen
                    </Button>
                </template>
            </DataTable>
        </div>

        <ResourceTypeForm
            :open="formOpen"
            :resource-type="editingResourceType"
            @update:open="formOpen = $event"
        />
    </AppLayout>
</template>
