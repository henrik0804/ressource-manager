<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/ResourceQualificationController';
import type { Column } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/resource-qualifications';
import type { BreadcrumbItem } from '@/types';
import type {
    Paginated,
    Qualification,
    Resource,
    ResourceQualification,
} from '@/types/models';

import ResourceQualificationForm from './ResourceQualificationForm.vue';

interface EnumOption {
    value: string;
    label: string;
}

const qualificationLevelLabels: Record<string, string> = {
    beginner: 'Anfänger',
    intermediate: 'Fortgeschritten',
    advanced: 'Erfahren',
    expert: 'Experte',
};

interface Props {
    resourceQualifications: Paginated<ResourceQualification>;
    resources: Pick<Resource, 'id' | 'name'>[];
    qualifications: Pick<Qualification, 'id' | 'name'>[];
    levels: EnumOption[];
    search: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ressourcenqualifikationen', href: index().url },
];

const columns: Column<ResourceQualification>[] = [
    {
        key: 'resource',
        label: 'Ressource',
        render: (row) => row.resource?.name ?? '—',
    },
    {
        key: 'qualification',
        label: 'Qualifikation',
        render: (row) => row.qualification?.name ?? '—',
    },
    {
        key: 'level',
        label: 'Stufe',
        render: (row) =>
            row.level
                ? (qualificationLevelLabels[row.level] ?? row.level)
                : '—',
    },
];

function deleteAction(id: number) {
    return destroy(id);
}

const formOpen = ref(false);
const editingResourceQualification = ref<ResourceQualification | null>(null);

function openCreate() {
    editingResourceQualification.value = null;
    formOpen.value = true;
}

function openEdit(resourceQualification: ResourceQualification) {
    editingResourceQualification.value = resourceQualification;
    formOpen.value = true;
}
</script>

<template>
    <Head title="Ressourcenqualifikationen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Ressourcenqualifikationen"
                description="Verwalten Sie die Qualifikationen, die Ressourcen zugeordnet sind."
            />

            <DataTable
                :data="resourceQualifications"
                :columns="columns"
                :search="search"
                :delete-action="deleteAction"
                route-prefix="/resource-qualifications"
                search-placeholder="Ressourcenqualifikationen suchen..."
                empty-message="Keine Ressourcenqualifikationen gefunden."
                delete-title="Ressourcenqualifikation löschen"
                delete-description="Möchten Sie diese Ressourcenqualifikation wirklich löschen?"
                @edit="openEdit"
            >
                <template #toolbar>
                    <Button @click="openCreate">
                        <Plus class="mr-2 size-4" />
                        Qualifikation zuordnen
                    </Button>
                </template>
            </DataTable>
        </div>

        <ResourceQualificationForm
            :open="formOpen"
            :resource-qualification="editingResourceQualification"
            :resources="resources"
            :qualifications="qualifications"
            :levels="levels"
            @update:open="formOpen = $event"
        />
    </AppLayout>
</template>
