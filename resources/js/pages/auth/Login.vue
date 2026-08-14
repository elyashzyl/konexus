<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { APP_ROUTES, AUTH_ROUTES } from '@/constants/app';
import { extractError, extractFieldErrors } from '@/lib/api';
import { homePathForRoles, isAdmin } from '@/lib/roles';
import { loginSchema, type LoginFormValues } from '@/schemas/auth';
import { useAuthStore } from '@/stores/auth';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const form = ref<LoginFormValues>({
    email: '',
    password: '',
    remember: false,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = async () => {
    const parsed = loginSchema.safeParse(form.value);

    if (!parsed.success) {
        errors.value = Object.fromEntries(parsed.error.issues.map((issue) => [issue.path[0], issue.message]));

        return;
    }

    errors.value = {};
    processing.value = true;

    try {
        await auth.login(parsed.data);
        toast.success('Logged in successfully.');
        const redirect = route.query.redirect;

        if (isAdmin(auth.user?.roles) && typeof redirect === 'string') {
            await router.push(redirect);
        } else {
            await router.push(homePathForRoles(auth.user?.roles));
        }
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
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autofocus
                    tabindex="1"
                    autocomplete="email"
                    v-model="form.email"
                    placeholder="email@example.com"
                    class="h-9 px-2.5 py-1.5"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <TextLink :href="AUTH_ROUTES['forgot-password'].path" class="text-sm" :tabindex="5"> Forgot password? </TextLink>
                </div>
                <Input
                    id="password"
                    type="password"
                    required
                    tabindex="2"
                    autocomplete="current-password"
                    v-model="form.password"
                    placeholder="Password"
                    class="h-9 px-2.5 py-1.5"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between" tabindex="3">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" v-model:checked="form.remember" tabindex="4" />
                    <span>Remember me</span>
                </Label>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <Button
                    type="submit"
                    class="bg-gradient-to-r from-[#32483c] to-[hsl(26_57%_40%)]"
                    tabindex="4"
                    :disabled="processing"
                >
                    <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                    Log in
                </Button>
                <Button type="button" class="bg-gradient-to-r from-[#32483c] to-[hsl(26_57%_40%)]" tabindex="5" as-child>
                    <RouterLink :to="APP_ROUTES.landing.path">Landing Page</RouterLink>
                </Button>
            </div>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Don't have an account?
            <TextLink :href="AUTH_ROUTES.register.path" :tabindex="5">Sign up</TextLink>
        </div>
    </form>
</template>
