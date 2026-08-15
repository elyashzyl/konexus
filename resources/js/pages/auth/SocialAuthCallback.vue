<script setup lang="ts">
import { AUTH_ROUTES } from '@/constants/app';
import { homePathForRoles } from '@/lib/roles';
import { useAuthStore } from '@/stores/auth';
import { LoaderCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const statusMessage = ref('Completing sign-in…');

function safeIntended(value: unknown): string | null {
    if (typeof value !== 'string' || value === '') {
        return null;
    }

    if (value.startsWith('/') && !value.startsWith('//') && !value.includes('\\')) {
        return value;
    }

    return null;
}

function goToLogin(): void {
    router.replace({ path: AUTH_ROUTES.login.path });
}

onMounted(async () => {
    const error = route.query.social_error;

    if (typeof error === 'string' && error) {
        toast.error(error);
        goToLogin();

        return;
    }

    const token = route.query.token;

    if (typeof token !== 'string' || token === '') {
        toast.error('Sign-in did not return a valid session.');
        goToLogin();

        return;
    }

    try {
        const user = await auth.socialLogin(token);
        statusMessage.value = 'Signed in — taking you to your workspace.';

        const intended = safeIntended(route.query.intended);

        await router.replace(intended ?? homePathForRoles(user.roles));
    } catch (e) {
        toast.error(e instanceof Error ? e.message : 'Sign-in failed.');
        goToLogin();
    }
});
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 py-10 text-center">
        <LoaderCircle class="h-6 w-6 animate-spin text-primary" />
        <p class="text-sm text-muted-foreground">{{ statusMessage }}</p>
    </div>
</template>