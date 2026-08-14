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
import { RouterLink, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

const router = useRouter();
const auth = useAuthStore();

const form = ref<RegisterFormValues>({
    school_name: '',
    short_name: '',
    school_id: '',
    region: '',
    division: '',
    district: '',
    address: '',
    contact_number: '',
    school_email: '',
    website: '',
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
        toast.success('School registered successfully.');
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
            <div class="grid gap-3">
                <p class="text-sm font-medium text-muted-foreground">School details</p>

                <div class="grid gap-2">
                    <Label for="school_name">School name</Label>
                    <Input
                        id="school_name"
                        type="text"
                        required
                        tabindex="1"
                        autocomplete="organization"
                        v-model="form.school_name"
                        placeholder="e.g. Baguio Patriotic High School"
                        class="h-9 px-2.5 py-1.5"
                    />
                    <InputError :message="errors.school_name" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="short_name">Short name</Label>
                        <Input id="short_name" type="text" tabindex="2" v-model="form.short_name" placeholder="e.g. BPHS" class="h-9 px-2.5 py-1.5" />
                        <InputError :message="errors.short_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="school_id">School ID</Label>
                        <Input
                            id="school_id"
                            type="text"
                            tabindex="3"
                            v-model="form.school_id"
                            placeholder="DepEd / LIS ID"
                            class="h-9 px-2.5 py-1.5"
                        />
                        <InputError :message="errors.school_id" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="grid gap-2">
                        <Label for="region">Region</Label>
                        <Input id="region" type="text" tabindex="4" v-model="form.region" placeholder="Region" class="h-9 px-2.5 py-1.5" />
                        <InputError :message="errors.region" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="division">Division</Label>
                        <Input id="division" type="text" tabindex="5" v-model="form.division" placeholder="Division" class="h-9 px-2.5 py-1.5" />
                        <InputError :message="errors.division" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="district">District</Label>
                        <Input id="district" type="text" tabindex="6" v-model="form.district" placeholder="District" class="h-9 px-2.5 py-1.5" />
                        <InputError :message="errors.district" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="address">Address</Label>
                    <Input id="address" type="text" tabindex="7" v-model="form.address" placeholder="School address" class="h-9 px-2.5 py-1.5" />
                    <InputError :message="errors.address" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="contact_number">Contact number</Label>
                        <Input
                            id="contact_number"
                            type="text"
                            tabindex="8"
                            v-model="form.contact_number"
                            placeholder="+63 900 000 0000"
                            class="h-9 px-2.5 py-1.5"
                        />
                        <InputError :message="errors.contact_number" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="school_email">School email</Label>
                        <Input
                            id="school_email"
                            type="email"
                            tabindex="9"
                            v-model="form.school_email"
                            placeholder="school@example.com"
                            class="h-9 px-2.5 py-1.5"
                        />
                        <InputError :message="errors.school_email" />
                    </div>
                </div>
            </div>

            <div class="grid gap-3">
                <p class="text-sm font-medium text-muted-foreground">Administrator account</p>

                <div class="grid gap-2">
                    <Label for="name">Administrator name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        tabindex="10"
                        autocomplete="name"
                        v-model="form.name"
                        placeholder="Full name"
                        class="h-9 px-2.5 py-1.5"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        tabindex="11"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                        class="h-9 px-2.5 py-1.5"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="12"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="At least 8 characters"
                        class="h-9 px-2.5 py-1.5"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        tabindex="13"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                        class="h-9 px-2.5 py-1.5"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>
            </div>

            <div class="mt-2 grid grid-cols-2 gap-3">
                <Button type="submit" class="bg-gradient-to-r from-[#32483c] to-[hsl(26_57%_40%)]" tabindex="14" :disabled="processing">
                    <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                    Register school
                </Button>
                <Button type="button" class="bg-gradient-to-r from-[#32483c] to-[hsl(26_57%_40%)]" tabindex="15" as-child>
                    <RouterLink :to="APP_ROUTES.landing.path">Landing Page</RouterLink>
                </Button>
            </div>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already registered your school?
            <TextLink :href="AUTH_ROUTES.login.path" class="underline underline-offset-4" :tabindex="16">Log in</TextLink>
        </div>
    </form>
</template>
