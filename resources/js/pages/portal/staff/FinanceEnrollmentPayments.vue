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
import { Input } from '@/components/ui/input';
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
    CalendarCheck2,
    CheckCircle2,
    CircleAlert,
    Landmark,
    LoaderCircle,
    Receipt,
    RefreshCw,
    UserCheck,
    Wallet,
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
    payment_status: string | null;
    payment_method: string | null;
    down_payment: string | number | null;
    payment_schedule_date: string | null;
    payment_schedule_details: string | null;
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

const paymentTarget = ref<EnrollmentRecord | null>(null);
const paymentSaving = ref(false);
const paymentForm = ref<{
    payment_status: string;
    payment_method: string;
    down_payment: string;
    payment_schedule_date: string;
    payment_schedule_details: string;
}>({
    payment_status: 'paid',
    payment_method: 'cash',
    down_payment: '',
    payment_schedule_date: '',
    payment_schedule_details: '',
});

const paymentOpen = computed({
    get: () => paymentTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            paymentTarget.value = null;
        }
    },
});

const metrics = computed(() => [
    { label: 'Awaiting payment', value: statistics.value?.per_status['for-payment'] ?? 0, detail: 'Online transfer or cash at the cashier', icon: Wallet },
    { label: 'With the principal', value: statistics.value?.per_status['for-principal-approval'] ?? 0, detail: 'Paid — awaiting section assignment', icon: BadgeCheck },
    { label: 'Officially enrolled', value: statistics.value?.officially_enrolled ?? 0, detail: 'Completed enrollments this cycle', icon: UserCheck },
    { label: 'Active pipeline', value: statistics.value?.active ?? 0, detail: 'Records still in the admissions chain', icon: CalendarCheck2 },
]);

function statusTone(status: EnrollmentStatus): string {
    if (status === 'officially-enrolled') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    if (status === 'for-final-check') return 'border-primary/15 bg-primary/6 text-primary';
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

function openPaymentDialog(record: EnrollmentRecord): void {
    paymentTarget.value = record;
    paymentForm.value = {
        payment_status: record.payment_status ?? 'paid',
        payment_method: record.payment_method ?? 'cash',
        down_payment: record.down_payment ? String(record.down_payment) : '',
        payment_schedule_date: record.payment_schedule_date ?? '',
        payment_schedule_details: record.payment_schedule_details ?? '',
    };
}

async function submitPayment(): Promise<void> {
    if (!paymentTarget.value) {
        return;
    }

    if (!paymentForm.value.payment_status) {
        toast.error('Choose a payment status.');

        return;
    }

    paymentSaving.value = true;

    try {
        await api.post(`/enrollments/${paymentTarget.value.id}/record-payment`, {
            payment_status: paymentForm.value.payment_status,
            payment_method: paymentForm.value.payment_method,
            down_payment: paymentForm.value.down_payment ? Number(paymentForm.value.down_payment) : null,
            payment_schedule_date: paymentForm.value.payment_schedule_date || null,
            payment_schedule_details: paymentForm.value.payment_schedule_details.trim() || null,
        });
        toast.success('Payment recorded. The principal can now assign a section.');
        paymentTarget.value = null;
        await load();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        paymentSaving.value = false;
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
                :icon="Landmark"
                eyebrow="Finance office"
                index="01"
                title="Enrollment payments"
                description="Mark elementary and high school tuition as paid — online or cash at the school — so the principal can assign a section."
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
                <p class="font-medium text-foreground">Enrollment payments could not be loaded.</p>
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
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">Collections queue</p>
                            <h2 class="mt-3 font-display text-3xl font-medium tracking-[-0.015em] text-foreground">
                                Enrollments awaiting payment
                            </h2>
                        </div>
                    </div>

                    <div
                        v-if="records.some((record) => record.status === 'for-payment')"
                        class="portal-rise mt-5 overflow-hidden rounded-2xl border border-border/60 bg-card"
                    >
                        <div
                            class="hidden grid-cols-[minmax(13rem,1.3fr)_minmax(10rem,0.9fr)_minmax(8rem,0.7fr)_auto] gap-5 border-b border-border/60 bg-muted/30 px-6 py-3 text-[10px] font-medium uppercase tracking-[0.16em] text-muted-foreground lg:grid"
                        >
                            <span>Learner / reference</span><span>Placement</span><span>Record state</span><span>Payment</span>
                        </div>
                        <article
                            v-for="record in records.filter((record) => record.status === 'for-payment')"
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
                                <Button size="sm" :disabled="busyId === record.id" @click="openPaymentDialog(record)">
                                    <Receipt class="size-3.5" /> Record payment
                                </Button>
                            </div>
                        </article>
                    </div>

                    <PortalEmptyState
                        v-else
                        class="mt-5"
                        :icon="Receipt"
                        title="No enrollments awaiting payment"
                        description="Completed applications reach this queue so families can pay online or in cash."
                    />
                </section>

                <footer class="portal-rise mt-16 border-t border-border/60 pt-6 text-xs text-muted-foreground" style="animation-delay: 220ms">
                    Recording a payment (online or cash) sends the learner to the principal for section and class assignment.
                </footer>
            </template>
        </div>

        <Dialog v-model:open="paymentOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-display text-2xl font-medium">Record enrollment payment</DialogTitle>
                    <DialogDescription>
                        Record the payment for {{ paymentTarget?.student?.name ?? 'this learner' }} · {{
                            paymentTarget?.reference_number ?? paymentTarget?.enrollment_number
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-5">
                    <div class="space-y-2">
                        <Label for="payment-status">Payment status</Label>
                        <Select v-model="paymentForm.payment_status">
                            <SelectTrigger id="payment-status"><SelectValue placeholder="Select payment status" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="paid">Paid</SelectItem>
                                <SelectItem value="partially-paid">Partially paid</SelectItem>
                                <SelectItem value="unpaid">Unpaid / pending</SelectItem>
                                <SelectItem value="waived">Waived</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label for="down-payment">Down payment (₱)</Label>
                        <Input id="down-payment" v-model="paymentForm.down_payment" type="number" min="0" step="0.01" placeholder="0.00" />
                    </div>

                    <div class="space-y-2">
                        <Label for="payment-schedule-date">Schedule date</Label>
                        <Input id="payment-schedule-date" v-model="paymentForm.payment_schedule_date" type="date" />
                    </div>

                    <div class="space-y-2">
                        <Label for="payment-schedule-details">Schedule details</Label>
                        <Textarea
                            id="payment-schedule-details"
                            v-model="paymentForm.payment_schedule_details"
                            rows="3"
                            placeholder="e.g. Balance payable in two installments"
                        />
                    </div>
                </div>

                <DialogFooter class="pt-2">
                    <Button type="button" variant="outline" @click="paymentTarget = null">Cancel</Button>
                    <Button type="button" :disabled="paymentSaving" @click="submitPayment">
                        <LoaderCircle v-if="paymentSaving" class="size-3.5 animate-spin" />
                        Record payment
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </main>
</template>