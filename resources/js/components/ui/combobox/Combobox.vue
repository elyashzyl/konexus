<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Check, ChevronsUpDown, Search } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

export interface ComboboxOption {
    label: string;
    value: string;
}

const props = withDefaults(
    defineProps<{
        modelValue: string;
        options: ComboboxOption[];
        placeholder?: string;
        emptyText?: string;
        disabled?: boolean;
        id?: string;
    }>(),
    {
        placeholder: 'Select an option…',
        emptyText: 'No results found.',
        disabled: false,
    },
);

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const open = ref(false);
const query = ref('');
const rootRef = ref<HTMLElement | null>(null);

const filtered = computed<ComboboxOption[]>(() => {
    const q = query.value.trim().toLowerCase();

    if (!q) {
        return props.options;
    }

    return props.options.filter((option) => option.label.toLowerCase().includes(q));
});

const selectedLabel = computed(() => props.options.find((option) => option.value === props.modelValue)?.label ?? '');

watch(() => props.modelValue, () => {
    query.value = '';
});

const toggle = () => {
    if (props.disabled) {
        return;
    }

    open.value = !open.value;

    if (open.value) {
        query.value = '';
    }
};

const select = (value: string) => {
    emit('update:modelValue', value);
    open.value = false;
};

const onDocumentClick = (event: MouseEvent) => {
    if (open.value && rootRef.value && !rootRef.value.contains(event.target as Node)) {
        open.value = false;
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="rootRef" class="relative">
        <button
            :id="id"
            type="button"
            class="flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="disabled"
            :aria-expanded="open"
            @click="toggle"
        >
            <span class="truncate" :class="selectedLabel ? 'text-foreground' : 'text-muted-foreground'">
                {{ selectedLabel || placeholder }}
            </span>
            <ChevronsUpDown class="size-4 shrink-0 opacity-50" />
        </button>

        <div
            v-if="open"
            class="absolute z-50 mt-1 w-full overflow-hidden rounded-md border border-border bg-popover text-popover-foreground shadow-md"
        >
            <div class="flex items-center gap-2 border-b border-border/60 px-2.5">
                <Search class="size-3.5 shrink-0 text-muted-foreground" />
                <Input
                    v-model="query"
                    type="text"
                    class="h-9 border-0 bg-transparent px-0 shadow-none focus-visible:ring-0"
                    placeholder="Begin typing for results…"
                />
            </div>

            <ul class="max-h-56 overflow-y-auto p-1">
                <li v-if="!filtered.length">
                    <p class="px-2 py-3 text-center text-sm text-muted-foreground">{{ emptyText }}</p>
                </li>
                <li v-for="option in filtered" :key="option.value">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-2 rounded-sm px-2 py-1.5 text-left text-sm text-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                        @click="select(option.value)"
                    >
                        <span class="truncate">{{ option.label }}</span>
                        <Check v-if="option.value === modelValue" class="size-4 shrink-0 text-primary" />
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>