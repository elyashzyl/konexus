<script setup lang="ts">
import ConfirmDialog from '@/components/crud/ConfirmDialog.vue';
import DataTable from '@/components/crud/DataTable.vue';
import FormDialog from '@/components/crud/FormDialog.vue';
import { useCrudStore } from '@/composables/useCrudStore';
import { loadOptions } from '@/lib/crud';
import type { CrudColumn, CrudField, CrudItem, CrudOption } from '@/types/crud';
import type { LucideIcon } from 'lucide-vue-next';
import { provide, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';

const props = withDefaults(
    defineProps<{
        icon?: LucideIcon;
        index?: string;
        eyebrow?: string;
        title: string;
        description?: string;
        resource: string;
        columns: CrudColumn[];
        fields: CrudField[];
        /** Field name → resource path used to populate remote select options. */
        optionSources?: Record<string, string>;
        createLabel?: string;
        searchable?: boolean;
        singularLabel?: string;
    }>(),
    {
        description: '',
        optionSources: () => ({}),
        createLabel: 'New',
        searchable: true,
        singularLabel: 'record',
    },
);

const store = useCrudStore({ resource: props.resource });

provide('crudStore', store);

const options = reactive<Record<string, CrudOption[]>>({});
const dialogOpen = ref(false);
const editing = ref<CrudItem | null>(null);
const confirm = ref<{ open: boolean; kind: 'remove' | 'restore' | 'force-remove'; item: CrudItem | null; busy: boolean }>({
    open: false,
    kind: 'remove',
    item: null,
    busy: false,
});

async function loadOptionSources(): Promise<void> {
    const entries = Object.entries(props.optionSources);

    await Promise.all(
        entries.map(async ([fieldName, resource]) => {
            const fieldDefinition = props.fields.find((field) => field.name === fieldName);

            options[fieldName] = await loadOptions(resource, fieldDefinition?.optionsLabelKey ?? 'name');
        }),
    );
}

function openCreate(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function openEdit(item: CrudItem): void {
    editing.value = item;
    dialogOpen.value = true;
}

function askRemove(item: CrudItem): void {
    confirm.value = { open: true, kind: 'remove', item, busy: false };
}

function askRestore(item: CrudItem): void {
    confirm.value = { open: true, kind: 'restore', item, busy: false };
}

function askForceRemove(item: CrudItem): void {
    confirm.value = { open: true, kind: 'force-remove', item, busy: false };
}

async function confirmAction(): Promise<void> {
    const target = confirm.value.item;

    if (!target) {
        return;
    }

    confirm.value.busy = true;

    try {
        switch (confirm.value.kind) {
            case 'remove':
                await store.remove(target.id);
                toast.success(`${props.singularLabel} deleted.`);
                break;
            case 'restore':
                await store.restore(target.id);
                toast.success(`${props.singularLabel} restored.`);
                break;
            case 'force-remove':
                await store.forceRemove(target.id);
                toast.success(`${props.singularLabel} permanently deleted.`);
                break;
        }

        confirm.value.open = false;
    } catch {
        toast.error(store.error ?? 'Something went wrong.');
    } finally {
        confirm.value.busy = false;
    }
}

async function handleSubmit(payload: Record<string, unknown>): Promise<void> {
    try {
        if (editing.value) {
            await store.update(editing.value.id, payload);
            toast.success(`${props.singularLabel} updated.`);
        } else {
            await store.create(payload);
            toast.success(`${props.singularLabel} created.`);
        }

        dialogOpen.value = false;
    } catch {
        toast.error(store.error ?? 'Something went wrong.');
    }
}

const confirmTitle = () => {
    switch (confirm.value.kind) {
        case 'remove':
            return 'Delete record';
        case 'restore':
            return 'Restore record';
        case 'force-remove':
            return 'Delete permanently';
    }
};

const confirmDescription = () => {
    switch (confirm.value.kind) {
        case 'remove':
            return 'This record will be soft-deleted and can be restored later from the Deleted records view.';
        case 'restore':
            return 'This record will be restored and become visible in the active list again.';
        case 'force-remove':
            return 'This record will be permanently deleted and cannot be recovered. This action cannot be undone.';
    }
};

loadOptionSources();
</script>

<template>
    <DataTable
        :columns="columns"
        :icon="icon"
        :index="index"
        :eyebrow="eyebrow"
        :title="title"
        :description="description"
        :create-label="createLabel"
        :searchable="searchable"
        @create="openCreate"
        @edit="openEdit"
        @remove="askRemove"
        @restore="askRestore"
        @force-remove="askForceRemove"
    />

    <FormDialog
        v-model:open="dialogOpen"
        :title="editing ? `Edit ${singularLabel}` : `New ${singularLabel}`"
        :fields="fields"
        :initial-values="editing"
        :options="options"
        :submitting="store.submitting"
        :field-errors="store.fieldErrors"
        :submit-label="editing ? 'Save changes' : 'Create'"
        @submit="handleSubmit"
    />

    <ConfirmDialog
        v-model:open="confirm.open"
        :title="confirmTitle()"
        :description="confirmDescription()"
        :confirm-label="confirm.kind === 'remove' ? 'Delete' : confirm.kind === 'restore' ? 'Restore' : 'Delete permanently'"
        :destructive="confirm.kind !== 'restore'"
        :busy="confirm.busy"
        @confirm="confirmAction"
    />
</template>
