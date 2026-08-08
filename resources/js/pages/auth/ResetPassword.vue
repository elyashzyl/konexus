<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AUTH_ROUTES } from '@/constants/app';
import { extractError, extractFieldErrors } from '@/lib/api';
import { resetPasswordSchema, type ResetPasswordFormValues } from '@/schemas/auth';
import { useAuthStore } from '@/stores/auth';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const form = ref<ResetPasswordFormValues>({
    token: typeof route.query.token === 'string' ? route.query.token : '',
    email: typeof route.query.email === 'string' ? route.query.email : '',
    password: '',
    password_confirmation: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = async () => {
    const parsed = resetPasswordSchema.safeParse(form.value);

    if (!parsed.success) {
        errors.value = Object.fromEntries(parsed.error.issues.map((issue) => [issue.path[0], issue.message]));

        return;
    }

    errors.value = {};
    processing.value = true;

    try {
        await auth.resetPassword(parsed.data);
        toast.success('Password reset successfully. You can now log in.');
        await router.push(AUTH_ROUTES.login.path);
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']));
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <form @submit.prevent="submit">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input id="email" type="email" name="email" autocomplete="email" v-model="form.email" class="mt-1 block w-full" readonly />
                <InputError :message="errors.email" class="mt-2" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    v-model="form.password"
                    class="mt-1 block w-full"
                    autofocus
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation"> Confirm Password </Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    v-model="form.password_confirmation"
                    class="mt-1 block w-full"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button type="submit" class="mt-4 w-full" :disabled="processing">
                <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                Reset password
            </Button>
        </div>
    </form>
</template>
