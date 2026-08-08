<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { APP_ROUTES } from '@/constants/app';
import { useRouter } from 'vue-router';

interface Props {
    status: number;
    title: string;
    description: string;
}

defineProps<Props>();

const router = useRouter();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted/40 px-6">
        <div class="flex flex-col items-center gap-6 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                <AppLogoIcon class="size-8 fill-current" />
            </div>

            <div class="space-y-2">
                <p class="text-6xl font-extrabold tracking-tight text-primary">{{ status }}</p>
                <h1 class="text-xl font-semibold">{{ title }}</h1>
                <p class="mx-auto max-w-md text-sm text-muted-foreground">{{ description }}</p>
            </div>

            <div class="flex items-center gap-3">
                <Button variant="outline" @click="router.back()">Go back</Button>
                <Button @click="router.push(APP_ROUTES.dashboard.path)">Back to dashboard</Button>
            </div>
        </div>
    </div>
</template>
