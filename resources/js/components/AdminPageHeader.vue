<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';

type Props = {
    icon?: LucideIcon;
    eyebrow?: string;
    index?: string;
    title: string;
    description?: string;
};

withDefaults(defineProps<Props>(), {
    icon: undefined,
    eyebrow: '',
    index: '',
    description: '',
});
</script>

<template>
    <header class="portal-rise relative">
        <div class="flex items-start justify-between gap-6">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <span
                        v-if="icon"
                        class="flex size-9 items-center justify-center rounded-lg bg-primary/8 text-primary ring-1 ring-primary/10"
                    >
                        <component :is="icon" class="size-4" />
                    </span>
                    <p
                        v-if="eyebrow"
                        class="text-[11px] font-medium tracking-[0.22em] text-primary uppercase"
                    >
                        {{ eyebrow }}
                    </p>
                    <span
                        v-if="index"
                        class="hidden items-center gap-2 text-[11px] tracking-[0.18em] text-muted-foreground/70 uppercase sm:flex"
                    >
                        <span class="size-0.5 rounded-full bg-primary/40" />
                        No. {{ index }}
                    </span>
                </div>
                <h1
                    class="mt-5 font-display text-[2.4rem] leading-[1.02] font-medium tracking-[-0.02em] text-foreground sm:text-5xl"
                >
                    {{ title }}
                </h1>
                <p
                    v-if="description"
                    class="mt-4 max-w-xl text-[15px] leading-7 text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <div class="hidden shrink-0 pt-1 sm:block">
                <slot name="actions" />
            </div>
        </div>
        <div class="editorial-rule mt-8 h-px" />
        <div class="mt-3 flex items-center justify-between">
            <span class="h-1 w-24 bg-primary/20" />
            <span class="h-1 w-1.5 rounded-full bg-primary/50" />
        </div>
    </header>
</template>