<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import api, { extractError } from '@/lib/api';
import type { Paginated } from '@/types/crud';
import {
    BadgeCheck,
    CheckCircle2,
    CircleAlert,
    ClipboardCheck,
    LoaderCircle,
    RefreshCw,
    ShieldCheck,
    Stamp,
    UserCheck,
    UsersRound,
    XCircle,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

type EnrollmentStatus =
    | 'draft'
    | 'pending'
    | 'for-principal-approval'
    | 'for-registrar-review'
    | 'for-payment'
    | 'for-final-check'
    | 'for-verification'
    | 'requirements-incomplete'
    | 'verified'
    | 'for-approval'
    | 'approved'
    | 'officially-enrolled'
    | 'rejected'
    | 'cancelled'
    | 'withdrawn'
    | 'transferred';

type EnrollmentRecord = {
    id: number;
    enrollment_number: string;
    reference_number: string | null;
    status: EnrollmentStatus;
    status_label: string;
    enrollment_type_label: string;
    enrollment_date: string | null;
    requirements_met: boolean;
    student: { name: string; student_number: string | null; lrn: string | null } | null;
    academic_year: { name: string } | null;
    grade_level: { id: number; name: string; education_level?: string | null } | null;
    section: { id: number; name: string } | null;
    campus: { name: string } | null;
};

type EnrollmentStatistics = {
    total: number;
    active: number;
    officially_enrolled: number;
    per_status: Partial<Record<EnrollmentStatus, number>>;
};

const loading = ref(true);
const refreshing = ref(false);
const busyId = ref<number | null>(null);
const records = ref<EnrollmentRecord[]>([]);
const statistics = ref<EnrollmentStatistics | null>(null);
const errorMessage = ref<string | null>(null);

const rejectTarget = ref<EnrollmentRecord | null>(null);
const rejectReason = ref('');
const rejectSaving = ref(false);

type PlacementRow = { id: number; name: string; grade_level_id?: number; max_capacity?: number | null };
const assignTarget = ref<EnrollmentRecord | null>(null);
const assignSaving = ref(false);
const gradeLevels = ref<PlacementRow[]>([]);
const sections = ref<(PlacementRow & { grade_level_id: number; max_capacity: number | null })[]>([]);
const assignForm = ref<{ grade_level_id: number | null; section_id: number | null }>({ grade_level_id: null, section_id: null });

const assignOpen = computed({
    get: () => assignTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            assignTarget.value = null;
        }
    },
});

const availableSections = computed(() =>
    assignForm.value.grade_level_id ? sections.value.filter((section) => section.grade_level_id === assignForm.value.grade_level_id) : [],
);

const rejectOpen = computed({
    get: () => rejectTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            rejectTarget.value = null;
            rejectReason.value = '';
        }
    },
});

const metrics = computed(() => [
    { label: 'Awaiting section', value: statistics.value?.per_status['for-principal-approval'] ?? 0, detail: 'Paid learners ready for class assignment', icon: Stamp },
    { label: 'Awaiting payment', value: statistics.value?.per_status['for-payment'] ?? 0, detail: 'Still with Accounting', icon: ClipboardCheck },
    { label: 'In pipeline', value: statistics.value?.active ?? 0, detail: 'Elementary and high school applications', icon: UsersRound },
    { label: 'Officially enrolled', value: statistics.value?.officially_enrolled ?? 0, detail: 'Completed admissions this cycle', icon: UserCheck },
]);

function statusTone(status: EnrollmentStatus): string {
    if (status === 'officially-enrolled') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    if (status === 'for-registrar-review') return 'border-primary/15 bg-primary/6 text-primary';
    if (status === 'for-payment') return 'border-sky-200 bg-sky-50 text-sky-700';
    if (status === 'rejected') return 'border-amber-200 bg-amber-50 text-amber-700';

    return 'border-border/80 bg-muted/50 text-muted-foreground';
}

function formatDate(value: string | null): string {
    if (!value) return '—';

    return new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
}

async function load(): Promise<void> {
    errorMessage.value = null;

    try {
        const [recordsResponse, statisticsResponse] = await Promise.all([
            api.get<{ data: Paginated<EnrollmentRecord> }>('/enrollments', {
                params: { per_page: 100, sort_by: 'created_at', sort_dir: 'desc' },
            }),
            api.get<{ data: EnrollmentStatistics }>('/enrollments/statistics'),
        ]);

        records.value = recordsResponse.data.data.items;
        statistics.value = statisticsResponse.data.data;
    } catch (error) {
        errorMessage.value = extractError(error);
    }
}

async function refresh(): Promise<void> {
    refreshing.value = true;
    await load();
    refreshing.value = false;
}

function openAssign(record: EnrollmentRecord): void {
    assignTarget.value = record;
    assignForm.value = {
        grade_level_id: record.grade_level?.id ?? null,
        section_id: record.section?.id ?? null,
    };
}

async function submitAssign(): Promise<void> {
    if (!assignTarget.value) {
        return;
    }

    if (!assignForm.value.grade_level_id || !assignForm.value.section_id) {
        toast.error('Choose a grade level and section.');
        return;
    }

    assignSaving.value = true;

    try {
        await api.post(`/enrollments/${assignTarget.value.id}/principal-approve`, {
            grade_level_id: assignForm.value.grade_level_id,
            section_id: assignForm.value.section_id,
        });
        toast.success(`${assignTarget.value.student?.name ?? 'Learner'} assigned and officially enrolled.`);
        assignTarget.value = null;
        await load();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        assignSaving.value = false;
    }
}

async function loadPlacementOptions(): Promise<void> {
    try {
        const [gradeLevelsResponse, sectionsResponse] = await Promise.all([
            api.get<{ data: Paginated<PlacementRow> }>('/grade-levels', {
                params: { per_page: 100, sort_by: 'sequence', sort_dir: 'asc' },
            }),
            api.get<{ data: Paginated<PlacementRow & { grade_level_id: number; max_capacity: number | null }> }>('/sections', {
                params: { per_page: 200, sort_by: 'name', sort_dir: 'asc' },
            }),
        ]);

        gradeLevels.value = gradeLevelsResponse.data.data.items;
        sections.value = sectionsResponse.data.data.items;
    } catch (error) {
        toast.error(extractError(error));
    }
}

function openReject(record: EnrollmentRecord): void {
    rejectTarget.value = record;
    rejectReason.value = '';
}

async function submitReject(): Promise<void> {
    if (!rejectTarget.value) {
        return;
    }

    if (!rejectReason.value.trim()) {
        toast.error('A rejection reason is required.');

        return;
    }

    rejectSaving.value = true;

    try {
        await api.post(`/enrollments/${rejectTarget.value.id}/reject`, { reason: rejectReason.value.trim() });
        toast.success(`${rejectTarget.value.student?.name ?? 'Enrollment'} rejected.`);
        rejectTarget.value = null;
        rejectReason.value = '';
        await load();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        rejectSaving.value = false;
    }
}

onMounted(async () => {
    await Promise.all([load(), loadPlacementOptions()]);
    loading.value = false;
});
</script>

<template>
    <main class="relative min-h-full">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12 lg:pt-16">
            <PortalPageHeader
                :icon="ShieldCheck"
                eyebrow="Leadership office"
                index="01"
                title="Section assignment"
                description="After Accounting marks tuition paid, assign elementary and high school learners to their sections and classes."
            >
                <template #actions>
                    <Button variant="outline" size="sm" :disabled="refreshing" @click="refresh"
                        ><RefreshCw class="size-3.5" :class="{ 'animate-spin': refreshing }" /> Refresh</Button
                    >
                </template>
            </PortalPageHeader>

            <div v-if="loading" class="mt-12 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="index in 4" :key="index" class="h-40 animate-pulse rounded-2xl bg-muted/60" />
            </div>

            <div v-else-if="errorMessage" class="mt-12 rounded-2xl border border-destructive/20 bg-destructive/5 p-6">
                <p class="font-medium text-foreground">Enrollment approvals could not be loaded.</p>
                <p class="mt-1 text-sm text-muted-foreground">{{ errorMessage }}</p>
                <Button class="mt-5" size="sm" @click="refresh"><RefreshCw class="size-3.5" /> Try again</Button>
            </div>

            <template v-else>
                <section class="portal-rise mt-12 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="(metric, index) in metrics"
                        :key="metric.label"
                        class="relative overflow-hidden rounded-2xl border border-border/60 bg-card p-6"
                    >
                        <div
                            class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent"
                        />
                        <div class="flex items-center justify-between">
                            <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                            <component :is="metric.icon" class="size-4 text-muted-foreground/50" />
                        </div>
                        <p class="mt-5 text-[13px] font-medium text-muted-foreground">{{ metric.label }}</p>
                        <p class="mt-1 font-display text-4xl font-medium tracking-[-0.02em] text-foreground">{{ metric.value }}</p>
                        <p class="mt-4 text-xs leading-5 text-muted-foreground">{{ metric.detail }}</p>
                    </article>
                </section>

                <section class="portal-rise mt-12" style="animation-delay: 160ms">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">Paid and ready</p>
                            <h2 class="mt-3 font-display text-3xl font-medium tracking-[-0.015em] text-foreground">
                                Assign a section
                            </h2>
                        </div>
                    </div>

                    <div
                        v-if="records.some((record) => record.status === 'for-principal-approval')"
                        class="portal-rise mt-5 overflow-hidden rounded-2xl border border-border/60 bg-card"
                    >
                        <div
                            class="hidden grid-cols-[minmax(13rem,1.3fr)_minmax(10rem,0.9fr)_minmax(8rem,0.7fr)_auto] gap-5 border-b border-border/60 bg-muted/30 px-6 py-3 text-[10px] font-medium uppercase tracking-[0.16em] text-muted-foreground lg:grid"
                        >
                            <span>Learner / reference</span><span>Requested placement</span><span>Record state</span><span>Decision</span>
                        </div>
                        <article
                            v-for="record in records.filter((record) => record.status === 'for-principal-approval')"
                            :key="record.id"
                            class="grid gap-4 border-b border-border/60 p-5 last:border-b-0 lg:grid-cols-[minmax(13rem,1.3fr)_minmax(10rem,0.9fr)_minmax(8rem,0.7fr)_auto] lg:items-center lg:gap-5 lg:px-6"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ record.student?.name ?? 'Applicant record pending' }}</p>
                                <p class="mt-1 font-mono text-[11px] text-muted-foreground">
                                    {{ record.reference_number ?? record.enrollment_number }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ record.enrollment_type_label }} · Submitted {{ formatDate(record.enrollment_date) }}
                                </p>
                            </div>
                            <div class="text-sm text-muted-foreground">
                                <p class="font-medium text-foreground">{{ record.grade_level?.name ?? 'Placement to be set' }}</p>
                                <p class="mt-1 text-xs">{{ record.campus?.name ?? 'Campus pending' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-[11px] font-medium" :class="statusTone(record.status)">{{
                                    record.status_label
                                }}</span>
                                <span
                                    class="inline-flex items-center gap-1 text-[11px]"
                                    :class="record.requirements_met ? 'text-emerald-700' : 'text-amber-700'"
                                >
                                    <CheckCircle2 v-if="record.requirements_met" class="size-3.5" />
                                    <CircleAlert v-else class="size-3.5" />
                                    {{ record.requirements_met ? 'Requirements met' : 'Requirements review' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 lg:justify-end">
                                <Button size="sm" variant="outline" :disabled="busyId === record.id" @click="openReject(record)">
                                    <XCircle class="size-3.5" /> Reject
                                </Button>
                                <Button size="sm" :disabled="busyId === record.id" @click="openAssign(record)">
                                    <BadgeCheck class="size-3.5" /> Assign section
                                </Button>
                            </div>
                        </article>
                    </div>

                    <PortalEmptyState
                        v-else
                        class="mt-5"
                        :icon="BadgeCheck"
                        title="No paid learners waiting"
                        description="Once Accounting marks tuition paid, learners appear here for section assignment."
                    />
                </section>

                <footer class="portal-rise mt-16 border-t border-border/60 pt-6 text-xs text-muted-foreground" style="animation-delay: 220ms">
                    Assigning a section officially enrolls the learner and builds the class roster.
                </footer>
            </template>
        </div>

        <Dialog v-model:open="assignOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-display text-2xl font-medium">Assign section</DialogTitle>
                    <DialogDescription>
                        Place {{ assignTarget?.student?.name ?? 'this learner' }} in an elementary or high school section.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-5">
                    <div class="space-y-2">
                        <Label for="assign-grade">Grade level</Label>
                        <Select
                            :model-value="assignForm.grade_level_id ? String(assignForm.grade_level_id) : undefined"
                            @update:model-value="(value: string) => { assignForm.grade_level_id = Number(value); assignForm.section_id = null; }"
                        >
                            <SelectTrigger id="assign-grade"><SelectValue placeholder="Select grade" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="grade in gradeLevels" :key="grade.id" :value="String(grade.id)">{{ grade.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-2">
                        <Label for="assign-section">Section / class</Label>
                        <Select
                            :model-value="assignForm.section_id ? String(assignForm.section_id) : undefined"
                            @update:model-value="(value: string) => { assignForm.section_id = Number(value); }"
                            :disabled="!assignForm.grade_level_id"
                        >
                            <SelectTrigger id="assign-section">
                                <SelectValue :placeholder="assignForm.grade_level_id ? 'Select section' : 'Choose a grade first'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="section in availableSections" :key="section.id" :value="String(section.id)">
                                    {{ section.name }}{{ section.max_capacity ? ` · ${section.max_capacity} seats` : '' }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <DialogFooter class="pt-2">
                    <Button type="button" variant="outline" @click="assignTarget = null">Cancel</Button>
                    <Button type="button" :disabled="assignSaving" @click="submitAssign">
                        <LoaderCircle v-if="assignSaving" class="size-3.5 animate-spin" />
                        Assign & officially enroll
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="rejectOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-display text-2xl font-medium">Reject enrollment</DialogTitle>
                    <DialogDescription>
                        Rejecting {{ rejectTarget?.student?.name ?? 'this application' }} returns it to the office with your reason.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2">
                    <Label for="reject-reason">Reason for rejection</Label>
                    <Textarea
                        id="reject-reason"
                        v-model="rejectReason"
                        rows="4"
                        placeholder="Explain why this application cannot proceed…"
                    />
                </div>

                <DialogFooter class="pt-2">
                    <Button type="button" variant="outline" @click="rejectTarget = null">Cancel</Button>
                    <Button type="button" variant="destructive" :disabled="rejectSaving" @click="submitReject">
                        <LoaderCircle v-if="rejectSaving" class="size-3.5 animate-spin" />
                        Reject enrollment
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </main>
</template>