<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { APP_ROUTES } from '@/constants/app';
import { useAuthStore } from '@/stores/auth';
import { AppWindow, ArrowRight } from 'lucide-vue-next';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

async function stop(): Promise<void> {
    await auth.stopImpersonating();
    await router.push(APP_ROUTES.dashboard.path);
}
</script>

<template>
    <div
        v-if="auth.isImpersonating"
        class="flex items-center justify-between gap-3 border-b border-primary/25 bg-primary/10 px-4 py-2"
    >
        <div class="flex min-w-0 items-center gap-2 text-xs text-primary">
            <AppWindow class="size-4 shrink-0" />
            <p class="truncate">
                Acting as <span class="font-semibold">{{ auth.user?.name }}</span>
                <span v-if="auth.impersonator" class="hidden text-primary/70 sm:inline">
                    · impersonated by {{ auth.impersonator.name }}
                </span>
            </p>
        </div>
        <Button variant="ghost" size="sm" class="shrink-0 text-primary" @click="stop">
            Stop impersonating <ArrowRight class="size-3.5" />
        </Button>
    </div>
</template>