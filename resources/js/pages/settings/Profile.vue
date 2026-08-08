<script setup lang="ts">
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { extractError, extractFieldErrors } from '@/lib/api';
import { useAuthStore } from '@/stores/auth';
import { TransitionRoot } from '@headlessui/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const auth = useAuthStore();

const form = ref({
    name: auth.user?.name ?? '',
    email: auth.user?.email ?? '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const saved = ref(false);

const submit = async () => {
    errors.value = {};
    processing.value = true;

    try {
        await auth.updateProfile(form.value);
        saved.value = true;
        toast.success('Profile updated.');
        setTimeout(() => {
            saved.value = false;
        }, 2500);
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']));
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="flex flex-col space-y-6">
        <HeadingSmall title="Profile information" description="Update your name and email address" />

        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Full name" />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Save</Button>

                <TransitionRoot
                    :show="saved"
                    enter="transition ease-in-out"
                    enter-from="opacity-0"
                    leave="transition ease-in-out"
                    leave-to="opacity-0"
                >
                    <p class="text-sm text-neutral-600">Saved.</p>
                </TransitionRoot>
            </div>
        </form>
    </div>

    <DeleteUser />
</template>
