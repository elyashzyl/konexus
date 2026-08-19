<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { Button } from '@/components/ui/button';
import api, { extractError } from '@/lib/api';
import type { Paginated } from '@/types/crud';
import {
    ArrowRight,
    Banknote,
    BookOpen,
    CheckCircle2,
    ClipboardList,
    GraduationCap,
    Laptop,
    LoaderCircle,
    Plus,
    RefreshCw,
    School,
    Send,
    UsersRound,
    Wallet,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { toast } from 'vue-sonner';

type EnrollmentStatus =
    | 'draft'
    | 'pending'
    | 'for-principal-approval'
    | 'for-registrar-review'
    | 'for-payment'
    | 'for-final-check'
    | 'officially-enrolled'
    | string;

type EnrollmentRecord = {
    id: number;
    enrollment_number: string;
    reference_number: string | null;
    status: EnrollmentStatus;
    status_label: string;
    enrollment_type_label: string;
    enrollment_date: string | null;
    payment_status: string | null;
    payment_method: string | null;
    department: string | null;
    incoming_level: string | null;
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
    per_status: Partial<Record<string, number>>;
};

const loading = ref(true);
const refreshing = ref(false);
const selectedBand = ref<'all' | 'elementary' | 'high-school'>('all');
const selectedStatus = ref<'all' | EnrollmentStatus>('all');
const busyId = ref<number | null>(null);
const records = ref<EnrollmentRecord[]>([]);
const statistics = ref<EnrollmentStatistics | null>(null);
const errorMessage = ref<string | null>(null);

const route = useRoute();

const portalBase = computed(() => {
    const match = route.path.match(/^\/portal\/staff\/[^/]+/);

    return match ? match[0] : '/portal/staff/registrar';
});

const workflowSteps = [
    { title: 'Online form', description: 'Families complete the DepEd-aligned elementary or high school application.', icon: Laptop },
    { title: 'Tuition payment', description: 'Settle online or pay cash at the cashier. Accounting marks the record paid.', icon: Wallet },
    { title: 'Principal assignment', description: 'The principal places the learner in a section or class.', icon: School },
    { title: 'Officially enrolled', description: 'The registrar record is locked and the class roster is created.', icon: GraduationCap },
];

function isElementary(record: EnrollmentRecord): boolean {
    const level = record.grade_level?.education_level ?? '';
    const department = record.department ?? '';

    return ['primary', 'elementary', 'grade-school', 'pre-school', 'kindergarten', 'early-childhood'].includes(level)
        || ['pre-school', 'grade-school'].includes(department);
}

function isHighSchool(record: EnrollmentRecord): boolean {
    const level = record.grade_level?.education_level ?? '';
    const department = record.department ?? '';

    return ['junior-high', 'senior-high'].includes(level) || ['junior-high', 'senior-high'].includes(department);
}

const bandedRecords = computed(() => {
    if (selectedBand.value === 'elementary') {
        return records.value.filter(isElementary);
    }

    if (selectedBand.value === 'high-school') {
        return records.value.filter(isHighSchool);
    }

    return records.value;
});

const filteredRecords = computed(() =>
    selectedStatus.value === 'all' ? bandedRecords.value : bandedRecords.value.filter((record) => record.status === selectedStatus.value),
);

const statusOptions = computed(() => [
    { value: 'all', label: 'All records', count: bandedRecords.value.length },
    { value: 'pending', label: 'Form completed', count: bandedRecords.value.filter((record) => record.status === 'pending').length },
    { value: 'for-payment', label: 'Awaiting payment', count: bandedRecords.value.filter((record) => record.status === 'for-payment').length },
    {
        value: 'for-principal-approval',
        label: 'With principal',
        count: bandedRecords.value.filter((record) => record.status === 'for-principal-approval').length,
    },
    {
        value: 'officially-enrolled',
        label: 'Officially enrolled',
        count: bandedRecords.value.filter((record) => record.status === 'officially-enrolled').length,
    },
]);

const metrics = computed(() => [
    { label: 'Pipeline', value: statistics.value?.active ?? 0, detail: 'Active elementary and high school applications', icon: UsersRound },
    { label: 'Awaiting payment', value: statistics.value?.per_status['for-payment'] ?? 0, detail: 'Ready for online or cash settlement', icon: Banknote },
    {
        label: 'With the principal',
        value: statistics.value?.per_status['for-principal-approval'] ?? 0,
        detail: 'Paid learners waiting for a section',
        icon: School,
    },
    { label: 'Officially enrolled', value: statistics.value?.officially_enrolled ?? 0, detail: 'Placed in a class this cycle', icon: CheckCircle2 },
]);

function statusTone(status: EnrollmentStatus): string {
    if (status === 'officially-enrolled') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    if (status === 'for-principal-approval') return 'border-primary/15 bg-primary/6 text-primary';
    if (status === 'for-payment') return 'border-sky-200 bg-sky-50 text-sky-700';
    if (status === 'pending') return 'border-amber-200 bg-amber-50 text-amber-800';

    return 'border-border/80 bg-muted/50 text-muted-foreground';
}

function formatDate(value: string | null): string {
    if (!value) return '—';

    return new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
}

function paymentLabel(record: EnrollmentRecord): string {
    if (!record.payment_method) return record.payment_status ?? 'Unpaid';

    const method = record.payment_method === 'online' ? 'Online' : 'Cash';

    return `${method}${record.payment_status ? ` · ${record.payment_status}` : ''}`;
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

async function releaseForPayment(record: EnrollmentRecord): Promise<void> {
    if (!window.confirm(`Release ${record.student?.name ?? 'this application'} for tuition payment (online or cash)?`)) {
        return;
    }

    busyId.value = record.id;

    try {
        await api.post(`/enrollments/${record.id}/forward-to-principal`);
        toast.success('Released to the payment queue.');
        await load();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        busyId.value = null;
    }
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
                :icon="ClipboardList"
                eyebrow="Registrar · Basic education"
                index="01"
                title="Enrollment desk"
                description="Philippine elementary and high school admissions: completed forms, tuition (online or cash), then principal section assignment."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" :disabled="refreshing" @click="refresh">
                            <RefreshCw class="size-3.5" :class="{ 'animate-spin': refreshing }" /> Refresh
                        </Button>
                        <RouterLink :to="`${portalBase}/enrollments/apply`">
                            <Button size="sm"><Plus class="size-3.5" /> Walk-in</Button>
                        </RouterLink>
                    </div>
                </template>
            </PortalPageHeader>

            <div v-if="loading" class="mt-12 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="index in 4" :key="index" class="h-40 animate-pulse rounded-2xl bg-muted/60" />
            </div>

            <div v-else-if="errorMessage" class="mt-12 rounded-2xl border border-destructive/20 bg-destructive/5 p-6">
                <p class="font-medium text-foreground">Enrollment desk could not be loaded.</p>
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
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <div class="flex items-center justify-between">
                            <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                            <component :is="metric.icon" class="size-4 text-muted-foreground/50" />
                        </div>
                        <p class="mt-5 text-[13px] font-medium text-muted-foreground">{{ metric.label }}</p>
                        <p class="mt-1 font-display text-4xl font-medium tracking-[-0.02em] text-foreground">{{ metric.value }}</p>
                        <p class="mt-4 text-xs leading-5 text-muted-foreground">{{ metric.detail }}</p>
                    </article>
                </section>

                <section class="portal-rise mt-10 grid gap-4 lg:grid-cols-3" style="animation-delay: 60ms">
                    <RouterLink
                        :to="`${portalBase}/enrollments`"
                        class="group rounded-2xl border border-border/60 bg-card p-6 transition hover:border-primary/30"
                    >
                        <BookOpen class="size-5 text-primary" />
                        <h3 class="mt-4 font-display text-xl font-medium">Enrollment ledger</h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">Full record set, documents, and historical school years.</p>
                        <span class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-primary">Open ledger <ArrowRight class="size-3.5" /></span>
                    </RouterLink>
                    <RouterLink
                        :to="`${portalBase.replace('/registrar', '/finance-officer')}/enrollment-payments`"
                        class="group rounded-2xl border border-border/60 bg-card p-6 transition hover:border-primary/30"
                    >
                        <Wallet class="size-5 text-primary" />
                        <h3 class="mt-4 font-display text-xl font-medium">Accounting payments</h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">Cashiers mark online transfers or over-the-counter cash as paid.</p>
                        <span class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-primary">Payment queue <ArrowRight class="size-3.5" /></span>
                    </RouterLink>
                    <RouterLink
                        :to="`${portalBase.replace('/registrar', '/principal')}/enrollment-approvals`"
                        class="group rounded-2xl border border-border/60 bg-card p-6 transition hover:border-primary/30"
                    >
                        <School class="size-5 text-primary" />
                        <h3 class="mt-4 font-display text-xl font-medium">Principal sections</h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">Paid learners are assigned to elementary or high school sections.</p>
                        <span class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-primary">Assignment desk <ArrowRight class="size-3.5" /></span>
                    </RouterLink>
                </section>

                <section class="portal-rise mt-12 overflow-hidden rounded-2xl border border-border/60 bg-card" style="animation-delay: 80ms">
                    <header class="border-b border-border/60 p-6">
                        <p class="text-[11px] font-medium uppercase tracking-[0.2em] text-primary">DepEd basic education flow</p>
                        <h2 class="mt-3 font-display text-2xl font-medium tracking-[-0.015em] text-foreground">
                            Form → Pay (online or cash) → Principal assigns section
                        </h2>
                    </header>
                    <div class="grid divide-y divide-border/60 md:grid-cols-4 md:divide-x md:divide-y-0">
                        <article v-for="(step, index) in workflowSteps" :key="step.title" class="p-6">
                            <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                            <span class="bg-primary/7 mt-5 flex size-9 items-center justify-center rounded-lg text-primary ring-1 ring-primary/10">
                                <component :is="step.icon" class="size-4" />
                            </span>
                            <h3 class="mt-4 font-display text-lg font-medium text-foreground">{{ step.title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ step.description }}</p>
                        </article>
                    </div>
                </section>

                <section class="portal-rise mt-12" style="animation-delay: 160ms">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">Admissions queue</p>
                            <h2 class="mt-3 font-display text-3xl font-medium tracking-[-0.015em] text-foreground">Manage applications</h2>
                        </div>
                    </div>

                    <div class="mt-7 flex flex-wrap gap-2">
                        <button
                            v-for="band in [
                                { value: 'all', label: 'All levels' },
                                { value: 'elementary', label: 'Elementary' },
                                { value: 'high-school', label: 'Junior & Senior High' },
                            ]"
                            :key="band.value"
                            type="button"
                            class="rounded-full border px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                selectedBand === band.value
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-border/70 bg-card text-muted-foreground hover:border-primary/25'
                            "
                            @click="selectedBand = band.value as 'all' | 'elementary' | 'high-school'"
                        >
                            {{ band.label }}
                        </button>
                    </div>

                    <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
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
                            @click="selectedStatus = option.value as 'all' | EnrollmentStatus"
                        >
                            {{ option.label }} <span class="ml-1 font-mono opacity-80">{{ option.count }}</span>
                        </button>
                    </div>

                    <div v-if="filteredRecords.length" class="portal-rise mt-5 overflow-hidden rounded-2xl border border-border/60 bg-card">
                        <div
                            class="hidden grid-cols-[minmax(13rem,1.3fr)_minmax(10rem,0.9fr)_minmax(8rem,0.7fr)_auto] gap-5 border-b border-border/60 bg-muted/30 px-6 py-3 text-[10px] font-medium uppercase tracking-[0.16em] text-muted-foreground lg:grid"
                        >
                            <span>Learner / reference</span><span>Level / campus</span><span>State</span><span>Next action</span>
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
                                    {{ record.enrollment_type_label }} · {{ formatDate(record.enrollment_date) }}
                                </p>
                            </div>
                            <div class="text-sm text-muted-foreground">
                                <p class="font-medium text-foreground">{{ record.grade_level?.name ?? record.incoming_level ?? 'Grade pending' }}</p>
                                <p class="mt-1 text-xs">
                                    {{ isElementary(record) ? 'Elementary' : isHighSchool(record) ? 'High school' : 'Basic education' }}
                                    {{ record.campus?.name ? ` · ${record.campus.name}` : '' }}
                                </p>
                                <p class="mt-1 text-xs">{{ record.section?.name ?? paymentLabel(record) }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-[11px] font-medium" :class="statusTone(record.status)">
                                    {{ record.status_label }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 lg:justify-end">
                                <Button v-if="record.status === 'pending'" size="sm" :disabled="busyId === record.id" @click="releaseForPayment(record)">
                                    <LoaderCircle v-if="busyId === record.id" class="size-3.5 animate-spin" />
                                    <Send v-else class="size-3.5" />
                                    Release for payment
                                </Button>
                                <RouterLink v-else :to="`${portalBase}/enrollments`">
                                    <Button size="sm" variant="outline">Open record <ArrowRight class="size-3.5" /></Button>
                                </RouterLink>
                            </div>
                        </article>
                    </div>

                    <PortalEmptyState
                        v-else
                        class="mt-5"
                        :icon="GraduationCap"
                        title="No records in this view"
                        description="Switch elementary or high school, or wait for families to finish the online form."
                    />
                </section>
            </template>
        </div>
    </main>
</template>
