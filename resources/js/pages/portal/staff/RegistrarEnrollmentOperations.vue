<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { Button } from '@/components/ui/button';
import api, { extractError } from '@/lib/api';
import type { Paginated } from '@/types/crud';
import {
    ArrowRight,
    BadgeCheck,
    CheckCircle2,
    CircleAlert,
    ClipboardCheck,
    FileCheck2,
    FilePenLine,
    GraduationCap,
    Laptop,
    LoaderCircle,
    Plus,
    RefreshCw,
    Send,
    UserCheck,
    UsersRound,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { toast } from 'vue-sonner';

type EnrollmentStatus =
    | 'draft'
    | 'pending'
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
    date_enrolled: string | null;
    requirements_met: boolean;
    student: { name: string; student_number: string | null; lrn: string | null } | null;
    academic_year: { name: string } | null;
    grade_level: { name: string } | null;
    section: { name: string } | null;
    campus: { name: string } | null;
};

type EnrollmentStatistics = {
    total: number;
    active: number;
    officially_enrolled: number;
    per_status: Partial<Record<EnrollmentStatus, number>>;
};

type Transition = {
    label: string;
    endpoint: 'verify' | 'approve' | 'complete';
    confirmation: string;
    icon: LucideIcon;
};

const loading = ref(true);
const refreshing = ref(false);
const selectedStatus = ref<'all' | EnrollmentStatus>('all');
const busyId = ref<number | null>(null);
const records = ref<EnrollmentRecord[]>([]);
const statistics = ref<EnrollmentStatistics | null>(null);
const errorMessage = ref<string | null>(null);

const workflowSteps = [
    { title: 'Online application', description: 'Family submits the guided public form.', icon: Laptop },
    { title: 'Requirements review', description: 'Registrar checks the submitted record and documents.', icon: ClipboardCheck },
    { title: 'Placement & approval', description: 'Grade, section, program, and capacity are confirmed.', icon: BadgeCheck },
    { title: 'Official enrollment', description: 'Roster and subject snapshots are materialized.', icon: GraduationCap },
];

const statusOptions = computed(() => [
    { value: 'all', label: 'All records', count: statistics.value?.total ?? 0 },
    { value: 'pending', label: 'New applications', count: statistics.value?.per_status.pending ?? 0 },
    { value: 'requirements-incomplete', label: 'Needs requirements', count: statistics.value?.per_status['requirements-incomplete'] ?? 0 },
    { value: 'verified', label: 'Ready for approval', count: statistics.value?.per_status.verified ?? 0 },
    { value: 'approved', label: 'Ready to enrol', count: statistics.value?.per_status.approved ?? 0 },
    { value: 'officially-enrolled', label: 'Officially enrolled', count: statistics.value?.officially_enrolled ?? 0 },
]);

const filteredRecords = computed(() =>
    selectedStatus.value === 'all' ? records.value : records.value.filter((record) => record.status === selectedStatus.value),
);

function selectStatus(status: 'all' | EnrollmentStatus): void {
    selectedStatus.value = status;
}

const metrics = computed(() => [
    { label: 'Applications & records', value: statistics.value?.total ?? 0, detail: 'Current admissions and enrollment pipeline', icon: UsersRound },
    { label: 'Active processing', value: statistics.value?.active ?? 0, detail: 'Records still moving through the office', icon: FilePenLine },
    {
        label: 'Ready to approve',
        value: statistics.value?.per_status.verified ?? 0,
        detail: 'Verified records awaiting placement approval',
        icon: FileCheck2,
    },
    {
        label: 'Officially enrolled',
        value: statistics.value?.officially_enrolled ?? 0,
        detail: 'Learners with finalized enrollment snapshots',
        icon: UserCheck,
    },
]);

function transitionFor(record: EnrollmentRecord): Transition | null {
    if (['pending', 'for-verification', 'requirements-incomplete'].includes(record.status)) {
        return {
            label: 'Verify requirements',
            endpoint: 'verify',
            confirmation:
                'Check the current requirement status and update this application? Incomplete documents will keep it in the requirements-review queue.',
            icon: ClipboardCheck,
        };
    }

    if (['verified', 'for-approval'].includes(record.status)) {
        return {
            label: 'Approve placement',
            endpoint: 'approve',
            confirmation: 'Approve the grade, section, and curriculum placement for this learner?',
            icon: BadgeCheck,
        };
    }

    if (record.status === 'approved') {
        return {
            label: 'Officially enrol',
            endpoint: 'complete',
            confirmation:
                'Complete official enrollment? This materializes the class roster and subject-enrollment snapshots for the selected curriculum.',
            icon: GraduationCap,
        };
    }

    return null;
}

function statusTone(status: EnrollmentStatus): string {
    if (status === 'officially-enrolled') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    if (status === 'approved' || status === 'verified') return 'border-primary/15 bg-primary/6 text-primary';
    if (status === 'requirements-incomplete' || status === 'rejected') return 'border-amber-200 bg-amber-50 text-amber-700';

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

async function runTransition(record: EnrollmentRecord, transition: Transition): Promise<void> {
    if (!window.confirm(transition.confirmation)) {
        return;
    }

    busyId.value = record.id;

    try {
        await api.post(`/enrollments/${record.id}/${transition.endpoint}`);
        toast.success(`${record.student?.name ?? 'Enrollment'} updated.`);
        await load();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        busyId.value = null;
    }
}

async function runNextTransition(record: EnrollmentRecord): Promise<void> {
    const transition = transitionFor(record);

    if (!transition) {
        return;
    }

    await runTransition(record, transition);
}

onMounted(async () => {
    await load();
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
                :icon="ClipboardCheck"
                eyebrow="Records office"
                index="01"
                title="Enrollment operations"
                description="Move applications from online intake through requirements, placement, approval, and official enrollment without losing the learner record behind each step."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" :disabled="refreshing" @click="refresh"
                            ><RefreshCw class="size-3.5" :class="{ 'animate-spin': refreshing }" /> Refresh</Button
                        >
                        <RouterLink to="/enrollment"
                            ><Button size="sm"><Laptop class="size-3.5" /> View public form</Button></RouterLink
                        >
                    </div>
                </template>
            </PortalPageHeader>

            <div v-if="loading" class="mt-12 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="index in 4" :key="index" class="h-40 animate-pulse rounded-2xl bg-muted/60" />
            </div>

            <div v-else-if="errorMessage" class="mt-12 rounded-2xl border border-destructive/20 bg-destructive/5 p-6">
                <p class="font-medium text-foreground">Enrollment operations could not be loaded.</p>
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

                <section class="portal-rise mt-12 overflow-hidden rounded-2xl border border-border/60 bg-card" style="animation-delay: 80ms">
                    <header class="border-b border-border/60 p-6">
                        <p class="text-[11px] font-medium uppercase tracking-[0.2em] text-primary">The public-to-office journey</p>
                        <h2 class="mt-3 font-display text-2xl font-medium tracking-[-0.015em] text-foreground">
                            One enrollment, one accountable path
                        </h2>
                    </header>
                    <div class="grid divide-y divide-border/60 md:grid-cols-4 md:divide-x md:divide-y-0">
                        <article v-for="(step, index) in workflowSteps" :key="step.title" class="p-6">
                            <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                            <span class="bg-primary/7 mt-5 flex size-9 items-center justify-center rounded-lg text-primary ring-1 ring-primary/10"
                                ><component :is="step.icon" class="size-4"
                            /></span>
                            <h3 class="mt-4 font-display text-lg font-medium text-foreground">{{ step.title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ step.description }}</p>
                        </article>
                    </div>
                    <div
                        class="flex flex-col gap-3 border-t border-border/60 bg-muted/30 px-6 py-4 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
                    >
                        <span
                            >Families can save and resume their online application; the registrar works from the same enrollment record after
                            submission.</span
                        >
                        <RouterLink to="/enrollment" class="inline-flex shrink-0 items-center gap-1.5 font-medium text-primary hover:underline"
                            >Open public enrollment <ArrowRight class="size-3.5"
                        /></RouterLink>
                    </div>
                </section>

                <section class="portal-rise mt-12" style="animation-delay: 160ms">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">Admissions queue</p>
                            <h2 class="mt-3 font-display text-3xl font-medium tracking-[-0.015em] text-foreground">
                                Work the next responsible action
                            </h2>
                        </div>
                        <RouterLink to="/school/enrollments" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline"
                            ><Plus class="size-3.5" /> Start a walk-in record</RouterLink
                        >
                    </div>

                    <div class="mt-7 flex gap-2 overflow-x-auto pb-1">
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            type="button"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                selectedStatus === option.value
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-border/70 bg-card text-muted-foreground hover:border-primary/25'
                            "
                            @click="selectStatus(option.value as 'all' | EnrollmentStatus)"
                        >
                            {{ option.label }} <span class="ml-1 font-mono opacity-80">{{ option.count }}</span>
                        </button>
                    </div>

                    <div v-if="filteredRecords.length" class="portal-rise mt-5 overflow-hidden rounded-2xl border border-border/60 bg-card">
                        <div
                            class="hidden grid-cols-[minmax(13rem,1.3fr)_minmax(10rem,0.9fr)_minmax(8rem,0.7fr)_auto] gap-5 border-b border-border/60 bg-muted/30 px-6 py-3 text-[10px] font-medium uppercase tracking-[0.16em] text-muted-foreground lg:grid"
                        >
                            <span>Learner / reference</span><span>Placement</span><span>Record state</span><span>Next action</span>
                        </div>
                        <article
                            v-for="record in filteredRecords"
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
                                <p class="font-medium text-foreground">{{ record.grade_level?.name ?? 'Placement pending' }}</p>
                                <p class="mt-1 text-xs">
                                    {{ record.section?.name ?? 'Section pending' }}{{ record.campus?.name ? ` · ${record.campus.name}` : '' }}
                                </p>
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
                                <template v-if="transitionFor(record)">
                                    <Button size="sm" :disabled="busyId === record.id" @click="runNextTransition(record)">
                                        <LoaderCircle v-if="busyId === record.id" class="size-3.5 animate-spin" />
                                        <component v-else :is="transitionFor(record)?.icon" class="size-3.5" />
                                        {{ transitionFor(record)?.label }}
                                    </Button>
                                </template>
                                <RouterLink v-else to="/school/enrollments"
                                    ><Button size="sm" variant="outline">Open record <ArrowRight class="size-3.5" /></Button
                                ></RouterLink>
                            </div>
                        </article>
                    </div>

                    <PortalEmptyState
                        v-else
                        class="mt-5"
                        :icon="Send"
                        title="No records in this queue"
                        description="Select another enrollment status, or open the public form to begin a new application."
                    />
                </section>

                <footer class="portal-rise mt-16 border-t border-border/60 pt-6 text-xs text-muted-foreground" style="animation-delay: 220ms">
                    Official enrollment creates the protected class-roster and subject-enrollment snapshots for the learner's selected curriculum
                    program.
                </footer>
            </template>
        </div>
    </main>
</template>
