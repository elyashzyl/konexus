<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import { LoaderCircle, Plus, Trash2, Users } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Application {
    id: number;
    reference_number: string;
}

interface SiblingRow {
    last_name: string;
    first_name: string;
    middle_name: string;
    extension_name: string;
    grade_level: string;
}

const props = withDefaults(defineProps<{
    application: Application;
    initialSiblings?: Record<string, unknown>[] | null;
    apiBase?: string;
}>(), {
    apiBase: '/public/enrollments',
});

const emit = defineEmits<{ submitted: [data: { siblings: Record<string, unknown>[] }] }>();

const rows = ref<SiblingRow[]>([]);
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const addRow = () => {
    rows.value.push({ last_name: '', first_name: '', middle_name: '', extension_name: '', grade_level: '' });
};

const removeRow = (index: number) => {
    rows.value.splice(index, 1);
};

const toPayload = (): Record<string, unknown>[] =>
    rows.value
        .filter((row) => row.first_name.trim() || row.last_name.trim() || row.grade_level.trim())
        .map((row) => ({
            last_name: row.last_name.trim(),
            first_name: row.first_name.trim(),
            middle_name: row.middle_name.trim() || null,
            extension_name: row.extension_name.trim() || null,
            grade_level: row.grade_level.trim(),
        }));

const validate = (): Record<string, string> => {
    const nextErrors: Record<string, string> = {};

    rows.value.forEach((row, index) => {
        if (!row.last_name.trim()) nextErrors[`siblings.${index}.last_name`] = 'Required';
        if (!row.first_name.trim()) nextErrors[`siblings.${index}.first_name`] = 'Required';
        if (!row.grade_level.trim()) nextErrors[`siblings.${index}.grade_level`] = 'Required';
    });

    return nextErrors;
};

const submit = async () => {
    errors.value = validate();

    if (Object.keys(errors.value).length) {
        return;
    }

    processing.value = true;

    try {
        const response = await api.put<{ data: { siblings: Record<string, unknown>[] } }>(
            `${props.apiBase}/${props.application.id}/details`,
            { siblings: toPayload() },
        );
        toast.success(rows.value.length ? 'Siblings saved.' : 'No siblings listed.');
        emit('submitted', { siblings: response.data.data.siblings });
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(
            Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']),
        );
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    if (props.initialSiblings?.length) {
        rows.value = props.initialSiblings.map((sibling) => ({
            last_name: String(sibling.last_name ?? ''),
            first_name: String(sibling.first_name ?? ''),
            middle_name: String(sibling.middle_name ?? ''),
            extension_name: String(sibling.extension_name ?? ''),
            grade_level: String(sibling.grade_level ?? ''),
        }));
    } else {
        addRow();
    }
});
</script>

<template>
    <Card class="relative overflow-hidden border-border/60 bg-card/60">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />

        <form @submit.prevent="submit">
            <CardHeader>
                <CardTitle>Siblings studying in this school</CardTitle>
                <CardDescription>
                    If the applicant has brothers or sisters studying in this school, list them below. Leave this empty if there are none.
                </CardDescription>
            </CardHeader>

            <CardContent class="grid gap-6">
                <div v-if="rows.length" class="grid gap-4">
                    <div
                        v-for="(row, index) in rows"
                        :key="index"
                        class="rounded-xl border border-border/60 bg-background/60 p-4"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Users class="size-4" />
                                Sibling {{ index + 1 }}
                            </p>
                            <Button type="button" variant="ghost" size="sm" class="h-8 gap-1.5 text-xs text-muted-foreground hover:text-destructive" @click="removeRow(index)">
                                <Trash2 class="size-3.5" />
                                Remove
                            </Button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label>Last Name</Label>
                                <Input v-model="row.last_name" type="text" class="h-9 px-2.5 py-1.5" />
                                <InputError :message="errors[`siblings.${index}.last_name`]" />
                            </div>
                            <div class="grid gap-2">
                                <Label>First Name</Label>
                                <Input v-model="row.first_name" type="text" class="h-9 px-2.5 py-1.5" />
                                <InputError :message="errors[`siblings.${index}.first_name`]" />
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label>Middle Name</Label>
                                <Input v-model="row.middle_name" type="text" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Extension</Label>
                                <Input v-model="row.extension_name" type="text" placeholder="Jr., Sr., III" class="h-9 px-2.5 py-1.5" />
                            </div>
                        </div>
                        <div class="mt-3 grid gap-2">
                            <Label>Grade Level</Label>
                            <Input v-model="row.grade_level" type="text" placeholder="e.g. Grade 5" class="h-9 px-2.5 py-1.5" />
                            <InputError :message="errors[`siblings.${index}.grade_level`]" />
                        </div>
                    </div>
                </div>

                <p v-else class="rounded-xl border border-dashed border-border/70 px-4 py-6 text-center text-sm text-muted-foreground">
                    No siblings added.
                </p>

                <Button type="button" variant="outline" class="h-9 gap-2 justify-self-start" @click="addRow">
                    <Plus class="size-4" />
                    Add sibling
                </Button>

                <Button type="submit" class="h-10" :disabled="processing">
                    <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                    {{ processing ? 'Saving…' : 'Continue to school fees plan' }}
                </Button>
            </CardContent>
        </form>
    </Card>
</template>