<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { CrudStore } from '@/composables/useCrudStore';
import type { CrudColumn, CrudItem } from '@/types/crud';
import type { LucideIcon } from 'lucide-vue-next';
import { Pencil, Plus, RefreshCcw, RotateCcw, Search, Trash2 } from 'lucide-vue-next';
import { computed, inject, onMounted } from 'vue';

const store = inject<CrudStore>('crudStore')!;

withDefaults(
    defineProps<{
        columns: CrudColumn[];
        icon?: LucideIcon;
        index?: string;
        eyebrow?: string;
        title: string;
        description?: string;
        createLabel?: string;
        searchable?: boolean;
        emptyMessage?: string;
    }>(),
    {
        description: '',
        createLabel: 'New',
        searchable: true,
        emptyMessage: 'No records found.',
    },
);

const emit = defineEmits<{
    (e: 'create'): void;
    (e: 'edit', item: CrudItem): void;
    (e: 'remove', item: CrudItem): void;
    (e: 'restore', item: CrudItem): void;
    (e: 'force-remove', item: CrudItem): void;
}>();

let searchTimer: ReturnType<typeof setTimeout> | undefined;

function onSearchInput(): void {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        store.page = 1;
        store.fetch();
    }, 350);
}

function onSort(column: CrudColumn): void {
    if (!column.sortable) {
        return;
    }

    if (store.sortBy === column.key) {
        store.sortDir = store.sortDir === 'asc' ? 'desc' : 'asc';
    } else {
        store.sortBy = column.key;
        store.sortDir = 'asc';
    }

    store.page = 1;
    store.fetch();
}

function onPageChange(page: number): void {
    store.page = page;
    store.fetch();
}

function toggleTrash(): void {
    store.trashed = !store.trashed;
    store.page = 1;
    store.fetch();
}

const startIndex = computed(() => {
    const pagination = store.pagination;

    return pagination && pagination.from ? pagination.from : 0;
});

const endIndex = computed(() => {
    const pagination = store.pagination;

    return pagination && pagination.to ? pagination.to : 0;
});

const total = computed(() => store.pagination?.total ?? 0);

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'object') {
        const record = value as Record<string, any>;

        if (typeof record.name === 'string') {
            return record.name;
        }

        return JSON.stringify(value);
    }

    if (typeof value === 'number') {
        return String(value);
    }

    if (typeof value === 'string') {
        const isoDate = /^\d{4}-\d{2}-\d{2}T/.test(value);

        if (isoDate) {
            return new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        }

        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        }
    }

    return String(value);
}

onMounted(() => {
    store.fetch();
});
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]" />

        <div class="relative w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="icon"
                :index="index"
                :eyebrow="eyebrow"
                :title="title"
                :description="description"
            >
                <template #actions>
                    <Button @click="emit('create')"><Plus class="size-4" /> {{ createLabel }}</Button>
                </template>
            </AdminPageHeader>

            <section class="portal-rise mt-10">
                <div class="flex flex-wrap items-center gap-2">
                    <div v-if="searchable" class="relative w-full max-w-sm">
                        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="store.search" class="pl-9" placeholder="Search…" @input="onSearchInput" />
                    </div>

                    <Button variant="outline" size="sm" class="gap-2" @click="toggleTrash">
                        <RefreshCcw v-if="store.trashed" class="size-4" />
                        <Trash2 v-else class="size-4" />
                        {{ store.trashed ? 'Active records' : 'Deleted records' }}
                    </Button>
                </div>
            </section>

            <section class="portal-rise mt-6" style="animation-delay: 120ms">
                <div class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead
                                    v-for="column in columns"
                                    :key="column.key"
                                    :class="column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : ''"
                                >
                                    <button
                                        v-if="column.sortable"
                                        type="button"
                                        class="inline-flex items-center gap-1 font-medium transition-colors hover:text-foreground"
                                        @click="onSort(column)"
                                    >
                                        {{ column.label }}
                                        <span class="text-xs opacity-60">{{
                                            store.sortBy === column.key ? (store.sortDir === 'asc' ? '↑' : '↓') : '↕'
                                        }}</span>
                                    </button>
                                    <template v-else>{{ column.label }}</template>
                                </TableHead>
                                <TableHead class="w-24 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="store.loading">
                                <TableCell :colspan="columns.length + 1">
                                    <div class="space-y-2 py-6">
                                        <Skeleton v-for="i in 4" :key="i" class="h-10" />
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-else-if="store.items.length === 0">
                                <TableCell :colspan="columns.length + 1" class="h-24 text-center text-muted-foreground">
                                    {{ store.error ?? emptyMessage }}
                                </TableCell>
                            </TableRow>
                            <template v-else>
                                <TableRow v-for="item in store.items" :key="item.id">
                                    <TableCell v-for="column in columns" :key="column.key" :class="column.align === 'right' ? 'text-right' : ''">
                                        <Badge v-if="column.key === 'is_active'" :variant="item.is_active ? 'secondary' : 'outline'">
                                            {{ item.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                        <template v-else>{{ formatValue(column.cell ? column.cell(item) : item[column.key]) }}</template>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <template v-if="store.trashed">
                                                <Button variant="ghost" size="sm" class="gap-1 px-2 text-foreground" @click="emit('restore', item)">
                                                    <RotateCcw class="size-4" />
                                                    Restore
                                                </Button>
                                                <Button variant="ghost" size="sm" class="gap-1 px-2 text-destructive" @click="emit('force-remove', item)">
                                                    <Trash2 class="size-4" />
                                                    Delete
                                                </Button>
                                            </template>
                                            <template v-else>
                                                <Button variant="ghost" size="sm" class="gap-1 px-2" @click="emit('edit', item)">
                                                    <Pencil class="size-4" />
                                                    Edit
                                                </Button>
                                                <Button variant="ghost" size="sm" class="gap-1 px-2 text-destructive" @click="emit('remove', item)">
                                                    <Trash2 class="size-4" />
                                                    Delete
                                                </Button>
                                            </template>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="store.pagination && total > 0" class="mt-6 flex flex-wrap items-center justify-between gap-4">
                    <p class="text-sm text-muted-foreground">
                        Showing <span class="font-medium text-foreground">{{ startIndex }}–{{ endIndex }}</span> of
                        <span class="font-medium text-foreground">{{ total }}</span> records
                    </p>

                    <Pagination :page="store.page" :total="total" :items-per-page="store.perPage" @update:page="onPageChange" />
                </div>
            </section>
        </div>
    </div>
</template>