<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { APP_ROUTES, AUTH_ROUTES } from '@/constants/app';
import { extractError, extractFieldErrors } from '@/lib/api';
import { registerSchema, type RegisterFormValues } from '@/schemas/auth';
import { useAuthStore } from '@/stores/auth';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

const router = useRouter();
const auth = useAuthStore();

const form = ref<RegisterFormValues>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = async () => {
    const parsed = registerSchema.safeParse(form.value);

    if (!parsed.success) {
        errors.value = Object.fromEntries(parsed.error.issues.map((issue) => [issue.path[0], issue.message]));

        return;
    }

    errors.value = {};
    processing.value = true;

    try {
        await auth.register(parsed.data);
        toast.success('Account created successfully.');
        await router.push(APP_ROUTES.dashboard.path);
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']));
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="flex flex-col gap-6">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" type="text" required autofocus tabindex="1" autocomplete="name" v-model="form.name" placeholder="Full name" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input id="email" type="email" required tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <Input
                    id="password"
                    type="password"
                    required
                    tabindex="3"
                    autocomplete="new-password"
                    v-model="form.password"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    required
                    tabindex="4"
                    autocomplete="new-password"
                    v-model="form.password_confirmation"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="processing">
                <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink :href="AUTH_ROUTES.login.path" class="underline underline-offset-4" :tabindex="6">Log in</TextLink>
        </div>
    </form>
</template>
