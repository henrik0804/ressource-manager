<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/QualificationController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/qualifications';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Qualification, ResourceType } from '@/types/models';

import QualificationForm from './QualificationForm.vue';

interface Props {
    qualifications: Paginated<Qualification>;
    resourceTypes: Pick<ResourceType, 'id' | 'name'>[];
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Qualifikationen', href: index().url },
];

const columns: Column<Qualification>[] = [
    { key: 'name', label: 'Name' },
    { key: 'description', label: 'Beschreibung' },
    {
        key: 'resource_type',
        label: 'Ressourcentyp',
        render: (row) => row.resource_type?.name ?? '—',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}

const formOpen = ref(false);
const editingQualification = ref<Qualification | null>(null);

function openCreate() {
    editingQualification.value = null;
    formOpen.value = true;
}

function openEdit(qualification: Qualification) {
    editingQualification.value = qualification;
    formOpen.value = true;
}
</script>

<template>
    <Head title="Qualifikationen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Qualifikationen"
                description="Verwalten Sie die verfügbaren Qualifikationen für Ressourcen."
            />

            <DataTable
                :data="qualifications"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/qualifications"
                search-placeholder="Qualifikationen suchen..."
                empty-message="Keine Qualifikationen gefunden."
                delete-title="Qualifikation löschen"
                delete-description="Möchten Sie diese Qualifikation wirklich löschen?"
                dependency-delete-description="Diese Qualifikation hat abhängige Daten, die unwiderruflich mitgelöscht werden."
                @edit="openEdit"
            >
                <template #toolbar>
                    <Button @click="openCreate">
                        <Plus class="mr-2 size-4" />
                        Qualifikation erstellen
                    </Button>
                </template>
            </DataTable>
        </div>

        <QualificationForm
            :open="formOpen"
            :qualification="editingQualification"
            :resource-types="resourceTypes"
            @update:open="formOpen = $event"
        />
    </AppLayout>
</template>
