<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import { LoaderCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Application {
    id: number;
    reference_number: string;
}

const props = withDefaults(
    defineProps<{
        application: Application;
        initialSettings?: Record<string, unknown> | null;
        apiBase?: string;
    }>(),
    {
        apiBase: '/public/enrollments',
    },
);

const emit = defineEmits<{ submitted: [data: { account_settings: Record<string, unknown> }] }>();

const username = ref('');
const importStatus = ref('pending');
const idChecking = ref(true);
const showAttendance = ref(true);
const showGrades = ref(true);
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = async () => {
    errors.value = {};

    if (username.value && !/^[A-Za-z0-9._-]{4,40}$/.test(username.value)) {
        errors.value.username = 'Use 4–40 letters, numbers, dots, underscores, or hyphens.';
        return;
    }

    processing.value = true;

    const payload = {
        username: username.value.trim() || null,
        import_status: importStatus.value,
        id_checking: idChecking.value,
        show_attendance_in_mobile: showAttendance.value,
        show_grades_in_portal: showGrades.value,
    };

    try {
        await api.put(`${props.apiBase}/${props.application.id}/details`, { account_settings: payload });
        toast.success('Account settings saved.');
        emit('submitted', { account_settings: payload });
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']));
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    const settings = props.initialSettings;

    if (!settings) {
        return;
    }

    username.value = String(settings.username ?? '');
    importStatus.value = String(settings.import_status ?? 'pending');
    idChecking.value = settings.id_checking !== false;
    showAttendance.value = settings.show_attendance_in_mobile !== false;
    showGrades.value = settings.show_grades_in_portal !== false;
});
</script>

<template>
    <Card class="relative overflow-hidden border-border/60 bg-card/60">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />

        <form @submit.prevent="submit">
            <CardHeader>
                <CardTitle>Other details</CardTitle>
                <CardDescription>Student account preferences and portal visibility settings.</CardDescription>
            </CardHeader>

            <CardContent class="grid gap-6">
                <section class="grid gap-3">
                    <p class="text-sm font-medium text-muted-foreground">Student account</p>
                    <div class="grid gap-2">
                        <Label for="username">User account / username</Label>
                        <Input id="username" v-model="username" type="text" class="h-9 px-2.5 py-1.5" placeholder="Preferred portal username" />
                        <InputError :message="errors.username" />
                    </div>
                </section>

                <section class="grid gap-4">
                    <p class="text-sm font-medium text-muted-foreground">System settings</p>
                    <div class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium">Import status</p>
                            <p class="text-xs text-muted-foreground">New applications start as pending import.</p>
                        </div>
                        <span class="rounded-full bg-muted px-3 py-1 text-xs font-medium capitalize text-muted-foreground">{{ importStatus }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium">ID checking</p>
                            <p class="text-xs text-muted-foreground">Require identity verification during admission.</p>
                        </div>
                        <Switch :checked="idChecking" @update:checked="idChecking = $event" />
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium">Show attendance in mobile app</p>
                            <p class="text-xs text-muted-foreground">Allow the family to view daily attendance.</p>
                        </div>
                        <Switch :checked="showAttendance" @update:checked="showAttendance = $event" />
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium">Show grades in student portal</p>
                            <p class="text-xs text-muted-foreground">Publish report cards once released by the registrar.</p>
                        </div>
                        <Switch :checked="showGrades" @update:checked="showGrades = $event" />
                    </div>
                </section>

                <Button type="submit" class="h-10" :disabled="processing">
                    <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                    {{ processing ? 'Saving…' : 'Continue to attachments' }}
                </Button>
            </CardContent>
        </form>
    </Card>
</template>
