<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

type PageItem = number | 'ellipsis';

const props = withDefaults(
    defineProps<{
        page: number;
        total: number;
        itemsPerPage?: number;
        siblingCount?: number;
    }>(),
    {
        itemsPerPage: 15,
        siblingCount: 1,
    },
);

const emit = defineEmits<{
    (e: 'update:page', page: number): void;
}>();

const lastPage = computed(() => Math.max(1, Math.ceil(props.total / props.itemsPerPage)));

const pageItems = computed<PageItem[]>(() => {
    const current = props.page;
    const last = lastPage.value;
    const sibling = props.siblingCount;

    const pages = new Set<number>([1, last, current]);
    for (let offset = -sibling; offset <= sibling; offset++) {
        pages.add(current + offset);
    }

    const sorted = [...pages].filter((page) => page >= 1 && page <= last).sort((a, b) => a - b);

    const items: PageItem[] = [];
    let previous = 0;

    for (const page of sorted) {
        if (page - previous > 1) {
            items.push('ellipsis');
        }

        items.push(page);
        previous = page;
    }

    return items;
});

function goTo(page: number): void {
    if (page < 1 || page > lastPage.value || page === props.page) {
        return;
    }

    emit('update:page', page);
}
</script>

<template>
    <nav class="flex items-center gap-1" aria-label="Pagination">
        <Button variant="ghost" class="size-9 gap-1 p-0" :disabled="page <= 1" @click="goTo(page - 1)">
            <ChevronLeft class="size-4" />
            <span class="sr-only">Previous page</span>
        </Button>

        <template v-for="(item, index) in pageItems" :key="index">
            <span v-if="item === 'ellipsis'" class="flex size-9 items-center justify-center text-sm text-muted-foreground">…</span>
            <Button v-else :variant="item === page ? 'default' : 'outline'" class="size-9 p-0" @click="goTo(item)">
                {{ item }}
            </Button>
        </template>

        <Button variant="ghost" class="size-9 gap-1 p-0" :disabled="page >= lastPage" @click="goTo(page + 1)">
            <ChevronRight class="size-4" />
            <span class="sr-only">Next page</span>
        </Button>
    </nav>
</template>
