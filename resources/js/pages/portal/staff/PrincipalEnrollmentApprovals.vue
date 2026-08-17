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
    grade_level: { id: number; name: string } | null;
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
    { label: 'Awaiting your approval', value: statistics.value?.per_status['for-principal-approval'] ?? 0, detail: 'Applications queued for the principal', icon: Stamp },
    { label: 'With the registrar', value: statistics.value?.per_status['for-registrar-review'] ?? 0, detail: 'Already approved and under placement', icon: ClipboardCheck },
    { label: 'Awaiting payment', value: statistics.value?.per_status['for-payment'] ?? 0, detail: 'Approved records with Accounting', icon: UsersRound },
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

async function approve(record: EnrollmentRecord): Promise<void> {
    if (!window.confirm(`Approve the enrollment of ${record.student?.name ?? 'this learner'}? It moves to the registrar for placement.`)) {
        return;
    }

    busyId.value = record.id;

    try {
        await api.post(`/enrollments/${record.id}/principal-approve`);
        toast.success(`${record.student?.name ?? 'Enrollment'} approved.`);
        await load();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        busyId.value = null;
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
                :icon="ShieldCheck"
                eyebrow="Leadership office"
                index="01"
                title="Enrollment approvals"
                description="Review applications that reached your desk and approve or reject them before the registrar proceeds with placement."
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
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">Awaiting your approval</p>
                            <h2 class="mt-3 font-display text-3xl font-medium tracking-[-0.015em] text-foreground">
                                Applications for the principal
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
                                <Button size="sm" :disabled="busyId === record.id" @click="approve(record)">
                                    <LoaderCircle v-if="busyId === record.id" class="size-3.5 animate-spin" />
                                    <BadgeCheck v-else class="size-3.5" /> Approve
                                </Button>
                            </div>
                        </article>
                    </div>

                    <PortalEmptyState
                        v-else
                        class="mt-5"
                        :icon="BadgeCheck"
                        title="Nothing awaiting your approval"
                        description="Submitted applications reach your desk as soon as they are ready for principal sign-off."
                    />
                </section>

                <footer class="portal-rise mt-16 border-t border-border/60 pt-6 text-xs text-muted-foreground" style="animation-delay: 220ms">
                    Approved applications move to the registrar for placement; rejected records are returned to the office with the reason.
                </footer>
            </template>
        </div>

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