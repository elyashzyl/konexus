<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { APP_ROUTES } from '@/constants/app';
import { RouterLink } from 'vue-router';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-svh flex-col items-center overflow-x-clip overflow-y-auto bg-background px-5 py-8 sm:px-8">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-[34rem] bg-[radial-gradient(60rem_26rem_at_50%_-30%,hsl(26_57%_40%/0.12),transparent)]"
        />

        <div class="portal-rise relative my-auto w-full max-w-lg">
            <div class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/60">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent"
                />

                <div class="flex flex-col items-center gap-2 pt-5">
                    <RouterLink :to="APP_ROUTES.dashboard.path" class="flex flex-col items-center gap-2 self-center font-medium">
                        <AppLogoIcon class="size-20 rounded-xl" />
                        <span
                            class="bg-gradient-to-r from-[#32483c] to-[hsl(26_57%_40%)] bg-clip-text font-display text-3xl font-semibold tracking-[-0.01em] text-transparent"
                        >
                            KONEXUS
                        </span>
                        <span class="sr-only">{{ title }}</span>
                    </RouterLink>
                </div>

                <div class="editorial-rule mx-6 mt-5 h-px" />

                <div class="space-y-2.5 px-6 pt-5 sm:px-8">
                    <h1 class="font-display text-xl font-medium tracking-[-0.02em] text-foreground">{{ title }}</h1>
                    <p class="text-sm leading-6 text-muted-foreground">{{ description }}</p>
                </div>

                <div class="px-6 pt-6 pb-8 sm:px-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>