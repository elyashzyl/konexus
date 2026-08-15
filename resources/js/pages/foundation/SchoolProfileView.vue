<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import { foundationModuleByKey } from '@/modules/foundation/config';
import { useAuthStore } from '@/stores/auth';
import type { CrudField } from '@/types/crud';
import { Building2, Plus, Save, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';

interface SchoolOption {
    id: number;
    name: string;
    short_name: string | null;
}

const auth = useAuthStore();

const module = foundationModuleByKey('school-profile');
const fields = module?.fields ?? [];

const isSuperAdmin = computed(() => auth.can('super-administrator'));

const schools = ref<SchoolOption[]>([]);
const selectedId = ref<number | null>(null);
const creating = ref(false);
const loading = ref(true);
const saving = ref(false);
const fieldErrors = ref<Record<string, string[]>>({});

const form = reactive<Record<string, any>>({});

function defaultFieldValue(field: CrudField): unknown {
    return field.type === 'switch' ? false : '';
}

function populate(source: Record<string, any>): void {
    for (const field of fields) {
        form[field.name] = source[field.name] !== undefined ? source[field.name] : defaultFieldValue(field);
    }
}

function resetForCreate(): void {
    for (const field of fields) {
        form[field.name] = defaultFieldValue(field);
    }
}

function buildPayload(): Record<string, unknown> {
    const payload: Record<string, unknown> = {};

    for (const field of fields) {
        const value = form[field.name];

        if (field.type === 'switch') {
            payload[field.name] = Boolean(value);
            continue;
        }

        const trimmed = typeof value === 'string' ? value.trim() : value;

        payload[field.name] = trimmed === '' ? undefined : trimmed;
    }

    return payload;
}

async function refreshSchoolsList(): Promise<void> {
    const { data } = await api.get<{ data: { items: SchoolOption[] } }>('/school-profiles', {
        params: { per_page: 100 },
    });

    schools.value = data.data.items;
}

async function loadSchool(id: number): Promise<void> {
    loading.value = true;
    creating.value = false;
    selectedId.value = id;
    fieldErrors.value = {};

    try {
        const { data } = await api.get<{ data: Record<string, any> }>(`/school-profiles/${id}`);
        populate(data.data);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
}

function startCreate(): void {
    resetForCreate();
    creating.value = true;
    selectedId.value = null;
    fieldErrors.value = {};
}

async function save(): Promise<void> {
    saving.value = true;
    fieldErrors.value = {};

    try {
        if (creating.value) {
            const { data } = await api.post<{ data: { id: number } }>('/school-profiles', buildPayload());
            toast.success('School profile created.');

            await refreshSchoolsList();
            await loadSchool(data.data.id);
        } else {
            await api.put(`/school-profiles/${selectedId.value}`, buildPayload());
            toast.success('School profile updated.');

            if (isSuperAdmin.value) {
                await refreshSchoolsList();
            }
        }
    } catch (error) {
        fieldErrors.value = extractFieldErrors(error);
        toast.error(extractError(error));
    } finally {
        saving.value = false;
    }
}

async function removeSchool(): Promise<void> {
    if (!selectedId.value || creating.value) {
        return;
    }

    if (!window.confirm('Delete this school profile? This cannot be undone.')) {
        return;
    }

    try {
        await api.delete(`/school-profiles/${selectedId.value}`);
        toast.success('School profile deleted.');

        await refreshSchoolsList();

        if (schools.value.length > 0) {
            await loadSchool(schools.value[0].id);
        } else {
            startCreate();
        }
    } catch (error) {
        toast.error(extractError(error));
    }
}

onMounted(async () => {
    if (isSuperAdmin.value) {
        await refreshSchoolsList();

        if (schools.value.length > 0) {
            await loadSchool(schools.value[0].id);
        } else {
            startCreate();
            loading.value = false;
        }
    } else {
        const ownId = auth.user?.school_profile_id;

        if (ownId) {
            await loadSchool(ownId);
        } else {
            loading.value = false;
            toast.error('Your account is not linked to a school.');
        }
    }
});
</script>

<template>
    <div class="relative">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="Building2"
                eyebrow="School"
                title="School Profile"
                description="Your school's identity and contact details. Super administrators can add and manage every school; school administrators edit the school they manage."
            >
                <template #actions>
                    <Button v-if="isSuperAdmin" variant="outline" :disabled="saving || loading" @click="startCreate">
                        <Plus class="size-4" />
                        New school
                    </Button>
                </template>
            </AdminPageHeader>

            <section v-if="isSuperAdmin" class="portal-rise mt-8 max-w-xl" style="animation-delay: 60ms">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Manage schools</CardTitle>
                        <CardDescription>Pick a school to view or edit its profile.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-end gap-2">
                            <div class="grid flex-1 gap-1.5">
                                <Label for="school-picker">School</Label>
                                <Select
                                    id="school-picker"
                                    :model-value="String(selectedId ?? '')"
                                    @update:model-value="(value) => loadSchool(Number(value))"
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a school…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="school in schools" :key="school.id" :value="String(school.id)">
                                            {{ school.name }}{{ school.short_name ? ` (${school.short_name})` : '' }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button
                                v-if="selectedId && !creating"
                                variant="destructive"
                                :disabled="saving || loading"
                                @click="removeSchool"
                            >
                                <Trash2 class="size-4" />
                                Delete
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section class="portal-rise mt-8" style="animation-delay: 120ms">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ creating ? 'New school profile' : 'School details' }}</CardTitle>
                        <CardDescription>
                            {{ creating ? 'Fill in the new school\u2019s information.' : 'View and update the school\u2019s details.' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="loading" class="space-y-3 py-2">
                            <Skeleton v-for="i in 6" :key="i" class="h-10" />
                        </div>

                        <form v-else class="grid grid-cols-1 gap-5 py-2 sm:grid-cols-2" @submit.prevent="save">
                            <div v-for="field in fields" :key="field.name" :class="{ 'col-span-full': field.fullWidth }">
                                <template v-if="field.type === 'switch'">
                                    <div class="flex items-center justify-between rounded-lg border p-4">
                                        <div>
                                            <Label :for="`field-${field.name}`" class="font-medium">{{ field.label }}</Label>
                                            <p v-if="field.hint" class="text-xs text-muted-foreground">{{ field.hint }}</p>
                                        </div>
                                        <Switch :id="`field-${field.name}`" v-model="form[field.name]" />
                                    </div>
                                </template>

                                <template v-else>
                                    <Label :for="`field-${field.name}`" class="mb-1.5 block">
                                        {{ field.label }}
                                        <span v-if="field.required" class="text-destructive"> *</span>
                                    </Label>

                                    <Select v-if="field.type === 'select'" v-model="form[field.name]">
                                        <SelectTrigger :id="`field-${field.name}`">
                                            <SelectValue :placeholder="field.placeholder ?? 'Select…'" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in field.options ?? []"
                                                :key="String(option.value)"
                                                :value="String(option.value)"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>

                                    <Textarea
                                        v-else-if="field.type === 'textarea'"
                                        :id="`field-${field.name}`"
                                        v-model="form[field.name]"
                                        :placeholder="field.placeholder"
                                    />

                                    <Input
                                        v-else
                                        :id="`field-${field.name}`"
                                        v-model="form[field.name]"
                                        :type="field.type === 'number' ? 'number' : (field.type ?? 'text')"
                                        :placeholder="field.placeholder"
                                    />

                                    <InputError :message="fieldErrors[field.name]?.[0]" />
                                </template>
                            </div>

                            <div class="col-span-full flex items-center justify-end gap-2 border-t pt-4">
                                <Button type="submit" :disabled="saving || loading">
                                    <Save class="size-4" />
                                    {{ saving ? 'Saving…' : creating ? 'Create school' : 'Save changes' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </section>
        </div>
    </div>
</template>