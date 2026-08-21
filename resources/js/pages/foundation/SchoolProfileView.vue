<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import { useWorkspaceStore } from '@/stores/workspace';
import type { CrudField } from '@/types/crud';
import { Building2, CheckCircle2, ChevronRight, Plus, Save, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';

interface SchoolOption {
    id: number;
    name: string;
    short_name: string | null;
    is_active?: boolean;
    is_primary?: boolean;
}

const auth = useAuthStore();
const workspace = useWorkspaceStore();

const module = foundationModuleByKey('school-profile');
const fields = module?.fields ?? [];

const isSuperAdmin = computed(() => auth.can('super-administrator'));
const activeSchoolId = computed(() => workspace.activeCampus?.school_profile_id ?? auth.user?.school_profile_id ?? null);

const schools = ref<SchoolOption[]>([]);
const selectedId = ref<number | null>(null);
const creating = ref(false);
const loading = ref(true);
const saving = ref(false);
const deletingId = ref<number | null>(null);
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

async function removeSchool(school: SchoolOption): Promise<void> {
    if (school.id === activeSchoolId.value || deletingId.value) {
        return;
    }

    if (!window.confirm(`Delete school "${school.name}"? Its profile and campuses will be archived and can be restored later.`)) {
        return;
    }

    deletingId.value = school.id;

    try {
        await api.delete(`/school-profiles/${school.id}`);
        toast.success('School profile deleted.');

        await Promise.all([refreshSchoolsList(), workspace.initialize(true)]);

        if (schools.value.length > 0) {
            await loadSchool(schools.value[0].id);
        } else {
            startCreate();
        }
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        deletingId.value = null;
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
                index="01"
                eyebrow="School setup"
                title="School profile"
                description="Your school's identity and contact details. Super administrators can add and manage every school; school administrators edit the school they manage."
            >
                <template #actions>
                    <Button v-if="isSuperAdmin" variant="outline" :disabled="saving || loading" @click="startCreate">
                        <Plus class="size-4" />
                        New school
                    </Button>
                </template>
            </AdminPageHeader>

            <section v-if="isSuperAdmin" class="portal-rise mt-8" style="animation-delay: 60ms">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">Directory</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight">Schools</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Pick a school card to view or edit its profile.</p>
                    </div>
                    <span
                        v-if="schools.length"
                        class="hidden items-center gap-2 rounded-full border border-primary/15 bg-primary/5 px-3 py-1 font-mono text-xs text-primary sm:inline-flex"
                    >
                        <span class="index-num">{{ schools.length }}</span> configured
                    </span>
                </div>

                <div v-if="loading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div v-for="item in 3" :key="item" class="h-48 animate-pulse rounded-xl border bg-muted/50" />
                </div>

                <div v-else-if="schools.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Card
                        v-for="(school, index) in schools"
                        :key="school.id"
                        class="group relative overflow-hidden border-border/60 bg-card/60 transition-all hover:-translate-y-0.5 hover:border-primary/35 hover:shadow-md"
                        :class="school.id === selectedId && !creating ? 'border-primary/45 shadow-sm ring-1 ring-primary/15' : ''"
                    >
                        <div v-if="school.id === selectedId && !creating" class="absolute inset-x-0 top-0 h-1 bg-primary" />
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <CardHeader class="pb-4 pt-6">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/10"><Building2 class="size-5" /></div>
                                    <div class="min-w-0">
                                        <CardTitle class="truncate text-base">{{ school.name }}</CardTitle>
                                        <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ school.short_name ?? `No. ${String(index + 1).padStart(2, '0')}` }}</p>
                                    </div>
                                </div>
                                <Badge variant="outline" class="shrink-0 text-[10px]">{{ school.is_active ? 'Active' : 'Inactive' }}</Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="flex min-h-10 items-center">
                                <span
                                    class="w-fit rounded-full border px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.12em]"
                                    :class="school.is_primary ? 'border-primary/25 bg-primary/5 text-primary' : 'border-border bg-muted/40 text-muted-foreground'"
                                >
                                    {{ school.is_primary ? 'Primary profile' : 'School profile' }}
                                </span>
                            </p>
                            <div class="flex items-center justify-between gap-3 border-t pt-3">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-muted-foreground hover:text-destructive"
                                    :disabled="deletingId === school.id || school.id === activeSchoolId"
                                    :title="school.id === activeSchoolId ? 'Switch to another campus before deleting this school' : 'Delete school'"
                                    @click="removeSchool(school)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    class="font-medium uppercase tracking-[0.12em] text-primary"
                                    @click="loadSchool(school.id)"
                                >
                                    Edit
                                    <ChevronRight class="size-4" />
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card v-else class="border-dashed bg-muted/20">
                    <CardContent class="flex flex-col items-center px-6 py-12 text-center">
                        <div class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"><Building2 class="size-6" /></div>
                        <h3 class="mt-4 font-display text-xl font-semibold">Register your first school</h3>
                        <p class="mt-2 max-w-md text-sm text-muted-foreground">Add the school profile before creating its campuses.</p>
                        <Button class="mt-5" @click="startCreate"><Plus class="size-4" />Create school</Button>
                    </CardContent>
                </Card>
            </section>

            <section class="portal-rise mt-8" style="animation-delay: 120ms">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">
                                    {{ creating ? 'New school profile' : 'School details' }}
                                </CardTitle>
                                <CardDescription class="mt-1">
                                    {{ creating ? 'Fill in the new school’s information.' : 'View and update the school’s details.' }}
                                </CardDescription>
                            </div>
                            <Badge variant="outline" class="w-fit shrink-0 gap-1.5 border-primary/25 bg-primary/5 px-3 py-1 text-primary">
                                <CheckCircle2 class="size-3.5" />
                                {{ creating ? 'Creating' : 'Editing' }}
                            </Badge>
                        </div>
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
                                        <Switch :id="`field-${field.name}`" :checked="form[field.name]" @update:checked="form[field.name] = $event" />
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