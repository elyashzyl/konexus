<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { TransitionRoot } from '@headlessui/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { extractError, extractFieldErrors } from '@/lib/api';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

const passwordInput = ref<HTMLInputElement>();
const currentPasswordInput = ref<HTMLInputElement>();

const form = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const saved = ref(false);

const updatePassword = async () => {
    errors.value = {};
    processing.value = true;

    try {
        await auth.changePassword(form.value);
        form.value = { current_password: '', password: '', password_confirmation: '' };
        saved.value = true;
        toast.success('Password updated.');
        setTimeout(() => {
            saved.value = false;
        }, 2500);
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']));

        if (errors.value.password) {
            form.value.password = '';
            form.value.password_confirmation = '';
            passwordInput.value?.focus();
        }

        if (errors.value.current_password) {
            form.value.current_password = '';
            currentPasswordInput.value?.focus();
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Update password" description="Ensure your account is using a long, random password to stay secure" />

        <form @submit.prevent="updatePassword" class="space-y-6">
            <div class="grid gap-2">
                <Label for="current_password">Current Password</Label>
                <Input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Current password"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">New password</Label>
                <Input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="New password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Save password</Button>

                <TransitionRoot
                    :show="saved"
                    enter="transition ease-in-out"
                    enter-from="opacity-0"
                    leave="transition ease-in-out"
                    leave-to="opacity-0"
                >
                    <p class="text-sm text-neutral-600">Saved</p>
                </TransitionRoot>
            </div>
        </form>
    </div>
</template>
