<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { platformApi, type SearchGroupItem } from '@/lib/platformApi';
import { Search } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';

const open = ref(false);
const query = ref('');
const results = ref<Record<string, SearchGroupItem[]>>({});
const total = ref(0);
const loading = ref(false);

let debounce: ReturnType<typeof setTimeout> | undefined;

const GROUP_LABELS: Record<string, string> = {
    students: 'Students',
    parents: 'Parents',
    people: 'People',
    enrollments: 'Enrollments',
    announcements: 'Announcements',
    sections: 'Sections',
    subjects: 'Subjects',
};

async function runSearch(): Promise<void> {
    const term = query.value.trim();
    if (!term) {
        results.value = {};
        total.value = 0;
        return;
    }

    loading.value = true;
    try {
        const data = await platformApi.search(term);
        results.value = data.groups;
        total.value = data.total;
    } catch {
        results.value = {};
        total.value = 0;
    } finally {
        loading.value = false;
    }
}

watch(query, () => {
    if (debounce) {
        clearTimeout(debounce);
    }
    debounce = setTimeout(runSearch, 250);
});

function onKeydown(event: KeyboardEvent): void {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        open.value = !open.value;
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Button variant="ghost" size="icon" aria-label="Search" @click="open = true">
        <Search class="size-4" />
    </Button>

    <Dialog :open="open" @update:open="(v: boolean) => (open = v)">
        <DialogContent class="top-24 sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Search everything</DialogTitle>
            </DialogHeader>

            <Input v-model="query" placeholder="Students, parents, enrollments, sections…" autofocus />

            <div v-if="loading" class="space-y-2 py-2">
                <div v-for="i in 3" :key="i" class="h-8 animate-pulse rounded bg-muted" />
            </div>

            <div v-else class="max-h-72 overflow-y-auto">
                <p v-if="total === 0" class="py-6 text-center text-sm text-muted-foreground">
                    {{ query ? 'No results found.' : 'Start typing to search.' }}
                </p>

                <section v-for="(items, group) in results" :key="group" class="py-1">
                    <h3 class="px-1 py-1 text-xs font-medium text-muted-foreground">
                        {{ GROUP_LABELS[group] ?? group }} · {{ items.length }}
                    </h3>
                    <RouterLink
                        v-for="item in items"
                        :key="`${group}-${item.id}`"
                        :to="item.route ?? { name: 'errors.404' }"
                        class="flex items-center justify-between rounded-md px-2 py-1.5 hover:bg-muted"
                        @click="open = false"
                    >
                        <span class="truncate text-sm">{{ item.label }}</span>
                        <span class="shrink-0 pl-2 text-xs text-muted-foreground">{{ item.subtitle }}</span>
                    </RouterLink>
                </section>
            </div>
        </DialogContent>
    </Dialog>
</template>
