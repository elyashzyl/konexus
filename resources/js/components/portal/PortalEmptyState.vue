<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';

type Props = {
    icon: LucideIcon;
    index?: string;
    title: string;
    description?: string;
};

withDefaults(defineProps<Props>(), {
    index: '',
    description: '',
});
</script>

<template>
    <div
        class="portal-rise relative overflow-hidden rounded-2xl border border-border/60 bg-card px-6 py-16 text-center sm:px-12 sm:py-20"
    >
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/30 to-transparent"
        />
        <div
            class="pointer-events-none absolute -top-24 left-1/2 size-64 -translate-x-1/2 rounded-full bg-primary/5 blur-3xl"
        />

        <div class="relative mx-auto flex max-w-md flex-col items-center">
            <div class="relative">
                <div
                    class="flex size-14 items-center justify-center rounded-full bg-primary/8 text-primary ring-1 ring-primary/10"
                >
                    <component :is="icon" class="size-6" />
                </div>
                <span
                    v-if="index"
                    class="absolute -top-1 -right-2 flex size-6 items-center justify-center rounded-full bg-secondary font-mono text-[10px] text-secondary-foreground ring-4 ring-card"
                >
                    {{ index }}
                </span>
            </div>

            <p
                class="mt-8 font-mono text-[11px] tracking-[0.28em] text-muted-foreground/80 uppercase"
            >
                Empty record
            </p>
            <h2
                class="mt-3 font-display text-3xl font-medium tracking-[-0.01em] text-foreground"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="mt-4 text-sm leading-7 text-muted-foreground"
            >
                {{ description }}
            </p>
            <div class="editorial-rule mt-8 h-px w-40" />
            <slot />
        </div>
    </div>
</template>
