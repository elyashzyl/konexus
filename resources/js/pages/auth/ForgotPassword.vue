<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { APP_ROUTES, AUTH_ROUTES } from '@/constants/app';
import { extractError, extractFieldErrors } from '@/lib/api';
import { forgotPasswordSchema, type ForgotPasswordFormValues } from '@/schemas/auth';
import { useAuthStore } from '@/stores/auth';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import { toast } from 'vue-sonner';

const auth = useAuthStore();

const form = ref<ForgotPasswordFormValues>({
    email: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const sent = ref(false);

const submit = async () => {
    const parsed = forgotPasswordSchema.safeParse(form.value);

    if (!parsed.success) {
        errors.value = Object.fromEntries(parsed.error.issues.map((issue) => [issue.path[0], issue.message]));

        return;
    }

    errors.value = {};
    processing.value = true;

    try {
        await auth.forgotPassword(parsed.data.email);
        sent.value = true;
        toast.success('Password reset link sent.');
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']));
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="space-y-6">
        <div v-if="sent" class="mb-4 text-center text-sm font-medium text-green-600">
            If an account exists for that email, a password reset link has been sent to it.
        </div>

        <form @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input id="email" type="email" name="email" autocomplete="off" v-model="form.email" autofocus placeholder="email@example.com" class="h-9 px-2.5 py-1.5" />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 grid grid-cols-2 gap-3">
                <Button type="submit" class="bg-gradient-to-r from-[#32483c] to-[hsl(26_57%_40%)]" :disabled="processing">
                    <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                    Email password reset link
                </Button>
                <Button type="button" class="bg-gradient-to-r from-[#32483c] to-[hsl(26_57%_40%)]" as-child>
                    <RouterLink :to="APP_ROUTES.landing.path">Landing Page</RouterLink>
                </Button>
            </div>
        </form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Or, return to</span>
            <TextLink :href="AUTH_ROUTES.login.path">log in</TextLink>
        </div>
    </div>
</template>
