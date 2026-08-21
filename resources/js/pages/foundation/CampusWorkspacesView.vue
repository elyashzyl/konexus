<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import { useAuthStore } from '@/stores/auth';
import { useWorkspaceStore } from '@/stores/workspace';
import type { CampusWorkspace } from '@/types';
import { Building2, CheckCircle2, ChevronRight, MapPin, Pencil, Plus, Save, Settings2, ShieldCheck, Trash2, X } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

interface SchoolOption {
    id: number;
    name: string;
    short_name: string | null;
}

const auth = useAuthStore();
const workspace = useWorkspaceStore();
const router = useRouter();

const campuses = ref<CampusWorkspace[]>([]);
const schoolProfiles = ref<SchoolOption[]>([]);
const loading = ref(true);
const saving = ref(false);
const deletingId = ref<number | null>(null);
const showCreate = ref(false);
const editingCampus = ref<CampusWorkspace | null>(null);
const fieldErrors = ref<Record<string, string[]>>({});

const form = reactive({
    school_profile_id: '',
    name: '',
    code: '',
    address: '',
    contact_number: '',
    is_active: true,
});

const canChooseSchoolProfile = computed(() => auth.can('super-administrator'));
const configuredSchoolProfileId = computed(() => workspace.activeCampus?.school_profile_id ?? auth.user?.school_profile_id ?? null);
const selectedSchoolName = computed(() => {
    const schoolId = Number(form.school_profile_id);
    return schoolProfiles.value.find((school) => school.id === schoolId)?.name ?? auth.user?.school?.name ?? 'your school profile';
});

function resetForm(): void {
    form.school_profile_id = String(configuredSchoolProfileId.value ?? '');
    form.name = '';
    form.code = '';
    form.address = '';
    form.contact_number = '';
    form.is_active = true;
    fieldErrors.value = {};
}

async function loadCampuses(): Promise<void> {
    const { data } = await api.get<{ data: { items: CampusWorkspace[] } }>('/campuses', { params: { per_page: 100, sort_by: 'name' } });
    campuses.value = data.data.items;
}

async function loadSchoolProfiles(): Promise<void> {
    if (!canChooseSchoolProfile.value) {
        return;
    }

    const { data } = await api.get<{ data: { items: SchoolOption[] } }>('/school-profiles', { params: { per_page: 100 } });
    schoolProfiles.value = data.data.items;
}

function openCreate(): void {
    editingCampus.value = null;
    resetForm();
    showCreate.value = true;
}

function openEdit(campus: CampusWorkspace): void {
    editingCampus.value = campus;
    form.school_profile_id = String(campus.school_profile_id ?? configuredSchoolProfileId.value ?? '');
    form.name = campus.name;
    form.code = campus.code ?? '';
    form.address = campus.address ?? '';
    form.contact_number = campus.contact_number ?? '';
    form.is_active = campus.is_active;
    fieldErrors.value = {};
    showCreate.value = true;
}

async function saveCampus(): Promise<void> {
    saving.value = true;
    fieldErrors.value = {};

    const payload = {
        school_profile_id: form.school_profile_id ? Number(form.school_profile_id) : undefined,
        name: form.name.trim(),
        code: form.code.trim() || undefined,
        address: form.address.trim() || undefined,
        contact_number: form.contact_number.trim() || undefined,
        is_active: form.is_active,
    };

    try {
        if (editingCampus.value) {
            await api.put(`/campuses/${editingCampus.value.id}`, payload);
            toast.success('Campus workspace updated.');
        } else {
            await api.post('/campuses', payload);
            toast.success('Campus workspace created and linked to its school profile.');
        }

        await Promise.all([loadCampuses(), workspace.initialize(true)]);
        showCreate.value = false;
        editingCampus.value = null;
    } catch (error) {
        fieldErrors.value = extractFieldErrors(error);
        toast.error(extractError(error));
    } finally {
        saving.value = false;
    }
}

async function openWorkspace(campus: CampusWorkspace): Promise<void> {
    try {
        await workspace.select(campus.id);
        toast.success(`Workspace changed to ${campus.name}.`);
        await router.push('/dashboard');
    } catch {
        toast.error('We could not change the campus workspace.');
    }
}

async function deleteCampus(campus: CampusWorkspace): Promise<void> {
    if (campus.id === workspace.activeCampus?.id || deletingId.value) {
        return;
    }

    if (!window.confirm(`Delete campus "${campus.name}"? Its workspace and records will be archived and can be restored later.`)) {
        return;
    }

    deletingId.value = campus.id;

    try {
        await api.delete(`/campuses/${campus.id}`);
        toast.success('Campus workspace deleted.');
        await Promise.all([loadCampuses(), workspace.initialize(true)]);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        deletingId.value = null;
    }
}

onMounted(async () => {
    try {
        await Promise.all([workspace.initialize(), loadCampuses(), loadSchoolProfiles()]);
        resetForm();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="relative min-h-full">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]" />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="Building2"
                index="02"
                eyebrow="School setup"
                title="Campus workspaces"
                description="Create a campus in its school profile, then switch operational context without mixing enrolments, classes, attendance, or gradebooks."
            >
                <template #actions>
                    <Button @click="openCreate">
                        <Plus class="size-4" />
                        New campus
                    </Button>
                </template>
            </AdminPageHeader>

            <section class="portal-rise mt-8 grid gap-4 lg:grid-cols-[1.45fr_0.85fr]" style="animation-delay: 60ms">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardContent class="p-0">
                        <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-4">
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/15">
                                    <Building2 class="size-6" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-primary">Active workspace</p>
                                    <h2 class="mt-1 truncate font-display text-2xl font-semibold tracking-tight">{{ workspace.activeCampus?.name ?? 'No campus selected' }}</h2>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ workspace.activeCampus?.school_profile?.name ?? auth.user?.school?.name ?? 'Create a campus to begin school operations.' }}</p>
                                </div>
                            </div>
                            <Badge variant="outline" class="w-fit gap-1.5 border-primary/25 bg-primary/5 px-3 py-1.5 text-primary">
                                <CheckCircle2 class="size-3.5" />
                                Workspace scoped
                            </Badge>
                        </div>
                        <div class="grid border-t sm:grid-cols-3">
                            <div class="flex items-center gap-2 border-b px-6 py-4 text-xs uppercase tracking-[0.12em] text-muted-foreground sm:border-b-0 sm:border-r">
                                <ShieldCheck class="size-4 text-primary" />
                                <span>Profile attached</span>
                            </div>
                            <div class="flex items-center gap-2 border-b px-6 py-4 text-xs uppercase tracking-[0.12em] text-muted-foreground sm:border-b-0 sm:border-r">
                                <Settings2 class="size-4 text-primary" />
                                <span>Saved per user</span>
                            </div>
                            <div class="flex items-center gap-2 px-6 py-4 text-xs uppercase tracking-[0.12em] text-muted-foreground">
                                <MapPin class="size-4 text-primary" />
                                <span>Operations local</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader class="pb-3">
                        <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">How workspaces behave</CardTitle>
                        <CardDescription>School identity is shared; operational records are not.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm text-muted-foreground">
                        <p><span class="font-medium text-foreground">Shared:</span> school profile, curriculum, grade levels, and approved catalogs.</p>
                        <p><span class="font-medium text-foreground">Campus-local:</span> enrollment placement, class rosters, attendance, offerings, and assessment records.</p>
                    </CardContent>
                </Card>
            </section>

            <section class="portal-rise mt-8" style="animation-delay: 120ms">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">Workspaces</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight">Available campuses</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Choose a card to enter that campus workspace.</p>
                    </div>
                    <span
                        v-if="campuses.length"
                        class="hidden items-center gap-2 rounded-full border border-primary/15 bg-primary/5 px-3 py-1 font-mono text-xs text-primary sm:inline-flex"
                    >
                        <span class="index-num">{{ campuses.length }}</span> configured
                    </span>
                </div>

                <div v-if="loading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div v-for="item in 3" :key="item" class="h-48 animate-pulse rounded-xl border bg-muted/50" />
                </div>

                <div v-else-if="campuses.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Card
                        v-for="(campus, index) in campuses"
                        :key="campus.id"
                        class="group relative overflow-hidden border-border/60 bg-card/60 transition-all hover:-translate-y-0.5 hover:border-primary/35 hover:shadow-md"
                        :class="campus.id === workspace.activeCampus?.id ? 'border-primary/45 shadow-sm ring-1 ring-primary/15' : ''"
                    >
                        <div v-if="campus.id === workspace.activeCampus?.id" class="absolute inset-x-0 top-0 h-1 bg-primary" />
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <CardHeader class="pb-4 pt-6">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/10"><Building2 class="size-5" /></div>
                                    <div class="min-w-0">
                                        <CardTitle class="truncate text-base">{{ campus.name }}</CardTitle>
                                        <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ campus.school_profile?.name ?? `No. ${String(index + 1).padStart(2, '0')}` }}</p>
                                    </div>
                                </div>
                                <Badge variant="outline" class="shrink-0 text-[10px]">{{ campus.is_active ? 'Active' : 'Inactive' }}</Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="flex min-h-10 items-start gap-2 text-sm text-muted-foreground"><MapPin class="mt-0.5 size-4 shrink-0 text-primary/80" />{{ campus.address || 'Address not yet configured' }}</p>
                            <div class="flex items-center justify-between gap-3 border-t pt-3">
                                <span class="font-mono text-[11px] uppercase tracking-[0.12em] text-muted-foreground">{{ campus.code || 'No code' }}</span>
                                <div class="flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-muted-foreground hover:text-primary"
                                        title="Edit campus"
                                        @click.stop="openEdit(campus)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-muted-foreground hover:text-destructive"
                                        :disabled="deletingId === campus.id || campus.id === workspace.activeCampus?.id"
                                        :title="campus.id === workspace.activeCampus?.id ? 'Switch away before deleting this campus' : 'Delete campus'"
                                        @click.stop="deleteCampus(campus)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                    <Button size="sm" variant="ghost" :disabled="!campus.is_active || campus.id === workspace.activeCampus?.id" @click="openWorkspace(campus)">
                                        {{ campus.id === workspace.activeCampus?.id ? 'Current' : 'Open' }}
                                        <ChevronRight class="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card v-else class="border-dashed bg-muted/20">
                    <CardContent class="flex flex-col items-center px-6 py-12 text-center">
                        <div class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"><Building2 class="size-6" /></div>
                        <h3 class="mt-4 font-display text-xl font-semibold">Set up your first campus</h3>
                        <p class="mt-2 max-w-md text-sm text-muted-foreground">Every campus is attached to a school profile before it can hold academic operations.</p>
                        <Button class="mt-5" @click="openCreate"><Plus class="size-4" />Create campus</Button>
                    </CardContent>
                </Card>
            </section>

            <section v-if="showCreate" class="portal-rise mt-8" style="animation-delay: 160ms">
                <Card class="relative overflow-hidden border-primary/25 shadow-md">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader class="flex-row items-start justify-between gap-4 space-y-0">
                        <div>
                            <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">
                                {{ editingCampus ? 'Edit campus workspace' : 'Create campus workspace' }}
                            </CardTitle>
                            <CardDescription class="mt-1">The school profile link is required before this campus can be used for enrollment and academic operations.</CardDescription>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge variant="outline" class="hidden gap-1.5 border-primary/25 bg-primary/5 px-3 py-1 text-primary sm:inline-flex">
                                <CheckCircle2 class="size-3.5" />
                                {{ editingCampus ? 'Editing' : 'Creating' }}
                            </Badge>
                            <Button size="icon" variant="ghost" aria-label="Close campus setup" @click="showCreate = false; editingCampus = null"><X class="size-4" /></Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form class="grid gap-5 sm:grid-cols-2" @submit.prevent="saveCampus">
                            <div class="sm:col-span-2">
                                <Label for="campus-school-profile">School profile <span class="text-destructive">*</span></Label>
                                <Select v-if="canChooseSchoolProfile" v-model="form.school_profile_id">
                                    <SelectTrigger id="campus-school-profile" class="mt-1.5"><SelectValue placeholder="Select a school profile" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="school in schoolProfiles" :key="school.id" :value="String(school.id)">{{ school.name }}{{ school.short_name ? ` (${school.short_name})` : '' }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-else class="mt-1.5 rounded-lg border bg-muted/30 px-3 py-2.5 text-sm font-medium">{{ selectedSchoolName }}</div>
                                <p class="mt-1.5 text-xs text-muted-foreground">This keeps campus operations under the correct school identity.</p>
                                <InputError :message="fieldErrors.school_profile_id?.[0]" />
                            </div>
                            <div>
                                <Label for="campus-name">Campus name <span class="text-destructive">*</span></Label>
                                <Input id="campus-name" v-model="form.name" class="mt-1.5" placeholder="e.g. Main Campus" />
                                <InputError :message="fieldErrors.name?.[0]" />
                            </div>
                            <div>
                                <Label for="campus-code">Campus code</Label>
                                <Input id="campus-code" v-model="form.code" class="mt-1.5" placeholder="e.g. MAIN" />
                                <InputError :message="fieldErrors.code?.[0]" />
                            </div>
                            <div class="sm:col-span-2">
                                <Label for="campus-address">Campus address</Label>
                                <Textarea id="campus-address" v-model="form.address" class="mt-1.5" placeholder="Street, barangay, city or municipality" />
                                <InputError :message="fieldErrors.address?.[0]" />
                            </div>
                            <div>
                                <Label for="campus-contact">Campus contact number</Label>
                                <Input id="campus-contact" v-model="form.contact_number" class="mt-1.5" placeholder="e.g. (074) 000 0000" />
                                <InputError :message="fieldErrors.contact_number?.[0]" />
                            </div>
                            <div class="flex items-center justify-between rounded-lg border p-3.5 sm:mt-6">
                                <div><Label for="campus-active">Open for operations</Label><p class="text-xs text-muted-foreground">Make this workspace selectable now.</p></div>
                                <Switch id="campus-active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                            </div>
                            <div class="flex justify-end gap-2 border-t pt-4 sm:col-span-2">
                                <Button type="button" variant="outline" @click="showCreate = false; editingCampus = null">Cancel</Button>
                                <Button type="submit" :disabled="saving || !form.name.trim() || !form.school_profile_id"><Save class="size-4" />{{ saving ? 'Saving…' : editingCampus ? 'Save changes' : 'Create campus workspace' }}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </section>
        </div>
    </div>
</template>
