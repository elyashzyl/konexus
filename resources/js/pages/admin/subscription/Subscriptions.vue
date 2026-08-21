<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { extractError, extractFieldErrors } from '@/lib/api';
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';
import { loadOptions } from '@/lib/crud';
import {
    subscriptionApi,
    type AuditEntry,
    type LicenseItem,
    type SubscriptionItem,
    type SubscriptionPlanItem,
    type TenantItem,
} from '@/lib/subscriptionApi';
import { Ban, Eye, HandCoins, History, KeyRound, Pause, Play, Plus, Radio, RefreshCw, Repeat, ShieldOff, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
const loading = ref(true);
const saving = ref(false);
const items = ref<SubscriptionItem[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = 15;
const search = ref('');
const statusFilter = ref<'all' | string>('all');
const fieldErrors = ref<Record<string, string[]>>({});

const tenants = ref<TenantItem[]>([]);
const schools = ref<{ value: string | number; label: string }[]>([]);
const plans = ref<SubscriptionPlanItem[]>([]);

const provisionOpen = ref(false);
const provisionForm = ref<{ tenant_id: string; plan_id: string; billing_cycle: string; auto_renewal: boolean; grace_days: number | null }>({
    tenant_id: '',
    plan_id: '',
    billing_cycle: 'monthly',
    auto_renewal: true,
    grace_days: 7,
});

const grantOpen = ref(false);
const grantForm = ref<{
    school_profile_id: string;
    plan_id: string;
    status: string;
    billing_cycle: string;
    start_date: string;
    expiration_date: string;
    amount: string;
    auto_renewal: boolean;
    grace_days: number | null;
    issue_license: boolean;
    notes: string;
}>({
    school_profile_id: '',
    plan_id: '',
    status: 'active',
    billing_cycle: 'monthly',
    start_date: '',
    expiration_date: '',
    amount: '',
    auto_renewal: false,
    grace_days: 7,
    issue_license: true,
    notes: '',
});

const actionOpen = ref(false);
const actionKind = ref<'renew' | 'suspend' | 'resume' | 'cancel'>('renew');
const actionTarget = ref<SubscriptionItem | null>(null);
const actionReason = ref('');

const changeOpen = ref(false);
const changeTarget = ref<SubscriptionItem | null>(null);
const changePlanId = ref('');
const changeReason = ref('');

const featureOpen = ref(false);
const featureTarget = ref<SubscriptionItem | null>(null);

const historyOpen = ref(false);
const historyTarget = ref<SubscriptionItem | null>(null);
const history = ref<AuditEntry[]>([]);

const licenseOpen = ref(false);
const licenseTarget = ref<SubscriptionItem | null>(null);
const licenses = ref<LicenseItem[]>([]);
const licenseLoading = ref(false);
const licenseAction = ref<'regenerate' | 'revoke'>('regenerate');
const licenseActionTarget = ref<LicenseItem | null>(null);
const licenseReason = ref('');

const licenseActionOpen = computed({
    get: () => licenseActionTarget.value !== null,
    set: (open: boolean) => {
        if (!open) licenseActionTarget.value = null;
    },
});

async function openLicenses(sub: SubscriptionItem): Promise<void> {
    licenseTarget.value = sub;
    licenses.value = [];
    licenseOpen.value = true;
    licenseLoading.value = true;
    try {
        const data = await subscriptionApi.licenses.index({ tenant_id: sub.tenant_id, per_page: 50 });
        licenses.value = data.items;
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        licenseLoading.value = false;
    }
}

async function revealLicense(license: LicenseItem): Promise<void> {
    try {
        const data = await subscriptionApi.licenses.show(license.id, true);
        const index = licenses.value.findIndex((item) => item.id === license.id);
        if (index !== -1) licenses.value[index] = data;
    } catch (error) {
        toast.error(extractError(error));
    }
}

function openLicenseAction(kind: 'regenerate' | 'revoke', license: LicenseItem): void {
    licenseAction.value = kind;
    licenseActionTarget.value = license;
    licenseReason.value = '';
}

function copyKey(key: string): void {
    void navigator.clipboard.writeText(key);
}

async function confirmLicenseAction(): Promise<void> {
    if (!licenseActionTarget.value) return;
    try {
        if (licenseAction.value === 'regenerate') {
            await subscriptionApi.licenses.regenerate(licenseActionTarget.value.id, licenseReason.value || undefined);
            toast.success('License regenerated.');
        } else {
            await subscriptionApi.licenses.revoke(licenseActionTarget.value.id, licenseReason.value || undefined);
            toast.success('License revoked.');
        }
        licenseActionTarget.value = null;
        licenseReason.value = '';
        await openLicenses(licenseTarget.value!);
    } catch (error) {
        toast.error(extractError(error));
    }
}

onMounted(async () => {
    async function load<T>(fn: Promise<T>): Promise<T | null> {
        try {
            return await fn;
        } catch (error) {
            toast.error(extractError(error));
            return null;
        }
    }

    const [tenantData, planData, schoolData] = await Promise.all([
        load(subscriptionApi.tenants.index({ per_page: 100 })),
        load(subscriptionApi.plans.index({ per_page: 100 })),
        load(loadOptions('school-profiles')),
    ]);
    if (tenantData) tenants.value = tenantData.items;
    if (planData) plans.value = planData.items;
    if (schoolData) schools.value = schoolData;
    await refresh();
});

async function refresh(): Promise<void> {
    loading.value = true;
    try {
        const data = await subscriptionApi.subscriptions.index({
            page: page.value,
            per_page: perPage,
            search: search.value || undefined,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
        });
        items.value = data.items;
        total.value = data.pagination.total;
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
}

useDebouncedSearch(search, () => {
    page.value = 1;
    refresh();
});

const statusLabel: Record<string, { label: string; variant: 'secondary' | 'default' | 'destructive' | 'outline' }> = {
    trial: { label: 'Trial', variant: 'secondary' },
    active: { label: 'Active', variant: 'default' },
    grace_period: { label: 'Grace', variant: 'outline' },
    past_due: { label: 'Past due', variant: 'outline' },
    suspended: { label: 'Suspended', variant: 'destructive' },
    cancelled: { label: 'Cancelled', variant: 'outline' },
    expired: { label: 'Expired', variant: 'destructive' },
};

function openProvision(): void {
    provisionForm.value = { tenant_id: '', plan_id: '', billing_cycle: 'monthly', auto_renewal: true, grace_days: 7 };
    fieldErrors.value = {};
    provisionOpen.value = true;
}

async function provision(): Promise<void> {
    saving.value = true;
    fieldErrors.value = {};
    try {
        const sub = await subscriptionApi.subscriptions.provision({
            tenant_id: Number(provisionForm.value.tenant_id),
            plan_id: Number(provisionForm.value.plan_id),
            billing_cycle: provisionForm.value.billing_cycle,
            auto_renewal: provisionForm.value.auto_renewal,
            grace_days: provisionForm.value.grace_days ?? undefined,
        });
        toast.success(`Subscription ${sub.subscription_code} provisioned.`);
        provisionOpen.value = false;
        await refresh();
    } catch (error) {
        fieldErrors.value = extractFieldErrors(error);
        toast.error(extractError(error));
    } finally {
        saving.value = false;
    }
}

function openGrant(): void {
    grantForm.value = {
        school_profile_id: '',
        plan_id: '',
        status: 'active',
        billing_cycle: 'monthly',
        start_date: '',
        expiration_date: '',
        amount: '',
        auto_renewal: false,
        grace_days: 7,
        issue_license: true,
        notes: '',
    };
    fieldErrors.value = {};
    grantOpen.value = true;
}

async function grant(): Promise<void> {
    saving.value = true;
    fieldErrors.value = {};
    try {
        const sub = await subscriptionApi.subscriptions.grant({
            school_profile_id: Number(grantForm.value.school_profile_id),
            plan_id: Number(grantForm.value.plan_id),
            status: grantForm.value.status,
            billing_cycle: grantForm.value.billing_cycle,
            start_date: grantForm.value.start_date || undefined,
            expiration_date: grantForm.value.expiration_date || undefined,
            amount: grantForm.value.amount ? Number(grantForm.value.amount) : undefined,
            auto_renewal: grantForm.value.auto_renewal,
            grace_days: grantForm.value.grace_days ?? undefined,
            issue_license: grantForm.value.issue_license,
            notes: grantForm.value.notes || undefined,
        });
        toast.success(`Subscription ${sub.subscription_code} manually granted.`);
        grantOpen.value = false;
        await refresh();
    } catch (error) {
        fieldErrors.value = extractFieldErrors(error);
        toast.error(extractError(error));
    } finally {
        saving.value = false;
    }
}

function openAction(kind: 'renew' | 'suspend' | 'resume' | 'cancel', sub: SubscriptionItem): void {
    actionKind.value = kind;
    actionTarget.value = sub;
    actionReason.value = '';
    actionOpen.value = true;
}

async function confirmAction(): Promise<void> {
    if (!actionTarget.value) return;
    const id = actionTarget.value.id;
    const reason = actionReason.value || undefined;
    try {
        switch (actionKind.value) {
            case 'renew':
                await subscriptionApi.subscriptions.renew(id);
                toast.success('Subscription renewed.');
                break;
            case 'suspend':
                await subscriptionApi.subscriptions.suspend(id, reason);
                toast.success('Subscription suspended.');
                break;
            case 'resume':
                await subscriptionApi.subscriptions.resume(id, reason);
                toast.success('Subscription resumed.');
                break;
            case 'cancel':
                await subscriptionApi.subscriptions.cancel(id, reason);
                toast.success('Subscription cancelled.');
                break;
        }
        actionOpen.value = false;
        await refresh();
    } catch (error) {
        toast.error(extractError(error));
    }
}

function openChange(sub: SubscriptionItem): void {
    changeTarget.value = sub;
    changePlanId.value = String(sub.plan_id);
    changeReason.value = '';
    changeOpen.value = true;
}

async function confirmChange(): Promise<void> {
    if (!changeTarget.value || !changePlanId.value) return;
    try {
        await subscriptionApi.subscriptions.changePlan(changeTarget.value.id, Number(changePlanId.value), changeReason.value || undefined);
        toast.success('Plan changed.');
        changeOpen.value = false;
        await refresh();
    } catch (error) {
        toast.error(extractError(error));
    }
}

function openFeatures(sub: SubscriptionItem): void {
    featureTarget.value = sub;
    featureOpen.value = true;
}

async function toggleFeature(code: string, enabled: boolean): Promise<void> {
    if (!featureTarget.value) return;
    try {
        const sub = await subscriptionApi.subscriptions.toggleFeature(featureTarget.value.id, code, enabled);
        featureTarget.value = sub;
        const match = items.value.find((i) => i.id === sub.id);
        if (match) match.features = sub.features;
        toast.success(enabled ? 'Feature enabled.' : 'Feature disabled.');
    } catch (error) {
        toast.error(extractError(error));
    }
}

async function openHistory(sub: SubscriptionItem): Promise<void> {
    historyTarget.value = sub;
    history.value = [];
    historyOpen.value = true;
    try {
        const data = await subscriptionApi.subscriptions.history(sub.id, { per_page: 30 });
        history.value = data.items;
    } catch (error) {
        toast.error(extractError(error));
    }
}

const money = (v: number) => `₱${Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const lastPage = ref(1);
</script>

<template>
    <div class="relative">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="Radio"
                index="04"
                eyebrow="Platform"
                title="Subscriptions"
                description="Provision plans, manage lifecycle and enforce access for each tenant."
            >
                <template #actions>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="openGrant"><HandCoins class="size-4" /> Manual add</Button>
                        <Button @click="openProvision"><Plus class="size-4" /> Provision</Button>
                    </div>
                </template>
            </AdminPageHeader>

            <section class="portal-rise mt-10 flex flex-wrap items-center gap-2">
                <Input
                    v-model="search"
                    placeholder="Search code, tenant…"
                    class="w-56"
                    @keydown.enter="
                        page = 1;
                        refresh();
                    "
                />
                <Select
                    :model-value="statusFilter"
                    @update:model-value="
                        (v: string) => {
                            statusFilter = v;
                            page = 1;
                            refresh();
                        }
                    "
                >
                    <SelectTrigger class="w-44">
                        <SelectValue :placeholder="statusFilter === 'all' ? 'All statuses' : statusFilter" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        <SelectItem value="trial">Trial</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="grace_period">Grace period</SelectItem>
                        <SelectItem value="past_due">Past due</SelectItem>
                        <SelectItem value="suspended">Suspended</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                        <SelectItem value="expired">Expired</SelectItem>
                    </SelectContent>
                </Select>
                <Button
                    variant="ghost"
                    size="sm"
                    @click="
                        page = 1;
                        refresh();
                    "
                    >Apply</Button
                >
                <div class="ml-auto text-sm text-muted-foreground">{{ total }} subscriptions</div>
            </section>

            <section class="portal-rise mt-6" style="animation-delay: 120ms">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader>
                        <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">Subscriptions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="loading" class="space-y-2">
                            <Skeleton v-for="i in 6" :key="i" class="h-14" />
                        </div>

                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Subscription</TableHead>
                                    <TableHead>Tenant</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Renewal</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Features</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="sub in items" :key="sub.id">
                                    <TableCell>
                                        <div class="font-mono text-xs font-medium">{{ sub.subscription_code }}</div>
                                        <div class="text-xs text-muted-foreground">{{ sub.plan?.name ?? `Plan #${sub.plan_id}` }}</div>
                                    </TableCell>
                                    <TableCell class="text-sm">{{ sub.tenant?.name ?? `Tenant #${sub.tenant_id}` }}</TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Badge :variant="statusLabel[sub.status]?.variant ?? 'secondary'">{{
                                                statusLabel[sub.status]?.label ?? sub.status
                                            }}</Badge>
                                            <span v-if="sub.days_remaining !== null && sub.days_remaining < 15" class="text-xs text-orange-500"
                                                >{{ sub.days_remaining }}d</span
                                            >
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-xs">
                                        {{ sub.billing_cycle }}
                                        <div class="text-muted-foreground">{{ sub.expiration_date ?? '—' }}</div>
                                    </TableCell>
                                    <TableCell>{{ money(sub.amount) }}</TableCell>
                                    <TableCell>
                                        <button class="text-xs text-muted-foreground underline-offset-2 hover:underline" @click="openFeatures(sub)">
                                            {{ sub.features.length }} features
                                        </button>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button variant="ghost" size="icon" aria-label="Licenses" @click="openLicenses(sub)">
                                                <KeyRound class="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" aria-label="History" @click="openHistory(sub)">
                                                <History class="size-4" />
                                            </Button>
                                            <Button
                                                v-if="['active', 'trial'].includes(sub.status)"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Renew"
                                                @click="openAction('renew', sub)"
                                            >
                                                <Repeat class="size-4" />
                                            </Button>
                                            <Button
                                                v-if="!['suspended', 'cancelled', 'expired'].includes(sub.status)"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Suspend"
                                                @click="openAction('suspend', sub)"
                                            >
                                                <Pause class="size-4" />
                                            </Button>
                                            <Button
                                                v-if="sub.status === 'suspended'"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Resume"
                                                @click="openAction('resume', sub)"
                                            >
                                                <Play class="size-4" />
                                            </Button>
                                            <Button
                                                v-if="!['cancelled', 'expired'].includes(sub.status)"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Cancel"
                                                @click="openAction('cancel', sub)"
                                            >
                                                <XCircle class="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" aria-label="Change plan" @click="openChange(sub)">
                                                <Ban class="size-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div v-if="total > perPage" class="mt-4 flex items-center justify-end gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="page <= 1"
                                @click="
                                    page--;
                                    refresh();
                                "
                                >Previous</Button
                            >
                            <span class="text-sm text-muted-foreground">Page {{ page }} of {{ lastPage }}</span>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="page >= lastPage"
                                @click="
                                    page++;
                                    refresh();
                                "
                                >Next</Button
                            >
                        </div>
                    </CardContent>
                </Card>
            </section>

            <Dialog v-model:open="grantOpen">
                <DialogContent class="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Manually add subscription</DialogTitle>
                        <DialogDescription>Grant a subscription to a school directly, without an invoice or payment.</DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="grant">
                        <div class="space-y-2">
                            <Label for="grant-school">School</Label>
                            <Select v-model="grantForm.school_profile_id">
                                <SelectTrigger id="grant-school" class="w-full"><SelectValue placeholder="Select school" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="school in schools" :key="school.value" :value="String(school.value)">{{
                                        school.label
                                    }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="fieldErrors.school_profile_id" class="text-xs text-destructive">{{ fieldErrors.school_profile_id[0] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="grant-plan">Plan</Label>
                                <Select v-model="grantForm.plan_id">
                                    <SelectTrigger id="grant-plan" class="w-full"><SelectValue placeholder="Select plan" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="plan in plans" :key="plan.id" :value="String(plan.id)">{{ plan.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="fieldErrors.plan_id" class="text-xs text-destructive">{{ fieldErrors.plan_id[0] }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="grant-status">Status</Label>
                                <Select v-model="grantForm.status">
                                    <SelectTrigger id="grant-status" class="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="pending">Pending</SelectItem>
                                        <SelectItem value="trial">Trial</SelectItem>
                                        <SelectItem value="suspended">Suspended</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="fieldErrors.status" class="text-xs text-destructive">{{ fieldErrors.status[0] }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="grant-cycle">Cycle</Label>
                                <Select v-model="grantForm.billing_cycle">
                                    <SelectTrigger id="grant-cycle" class="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="monthly">Monthly</SelectItem>
                                        <SelectItem value="annual">Annual</SelectItem>
                                        <SelectItem value="custom">Custom</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-2">
                                <Label for="grant-amount">Amount</Label>
                                <Input
                                    id="grant-amount"
                                    type="number"
                                    step="0.01"
                                    placeholder="Plan price"
                                    :model-value="grantForm.amount"
                                    @update:model-value="grantForm.amount = String($event)"
                                />
                                <p v-if="fieldErrors.amount" class="text-xs text-destructive">{{ fieldErrors.amount[0] }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="grant-start">Start date</Label>
                                <Input id="grant-start" type="date" v-model="grantForm.start_date" />
                                <p v-if="fieldErrors.start_date" class="text-xs text-destructive">{{ fieldErrors.start_date[0] }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="grant-exp">Expiration date</Label>
                                <Input id="grant-exp" type="date" v-model="grantForm.expiration_date" />
                                <p v-if="fieldErrors.expiration_date" class="text-xs text-destructive">{{ fieldErrors.expiration_date[0] }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <Label for="grant-grace">Grace days</Label>
                                <Input
                                    id="grant-grace"
                                    type="number"
                                    :model-value="grantForm.grace_days === null ? '' : String(grantForm.grace_days)"
                                    @update:model-value="grantForm.grace_days = $event === '' ? null : Number($event)"
                                />
                            </div>
                            <div class="flex items-end gap-2 pb-2">
                                <Switch :checked="grantForm.auto_renewal" @update:checked="grantForm.auto_renewal = $event" />
                                <Label>Auto-renew</Label>
                            </div>
                            <div class="flex items-end gap-2 pb-2">
                                <Switch :checked="grantForm.issue_license" @update:checked="grantForm.issue_license = $event" />
                                <Label>Issue license</Label>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="grant-notes">Notes</Label>
                            <Textarea id="grant-notes" v-model="grantForm.notes" placeholder="e.g. Courtesy access for the pilot semester" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="grantOpen = false">Cancel</Button>
                            <Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Add subscription' }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="provisionOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Provision subscription</DialogTitle>
                        <DialogDescription>Create a subscription and issue a license for a tenant.</DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="provision">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="prov-tenant">Tenant</Label>
                                <Select v-model="provisionForm.tenant_id">
                                    <SelectTrigger id="prov-tenant" class="w-full"><SelectValue placeholder="Select tenant" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">{{ tenant.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="fieldErrors.tenant_id" class="text-xs text-destructive">{{ fieldErrors.tenant_id[0] }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="prov-plan">Plan</Label>
                                <Select v-model="provisionForm.plan_id">
                                    <SelectTrigger id="prov-plan" class="w-full"><SelectValue placeholder="Select plan" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="plan in plans" :key="plan.id" :value="String(plan.id)"
                                            >{{ plan.name }} — {{ money(plan.monthly_price) }}/mo</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                                <p v-if="fieldErrors.plan_id" class="text-xs text-destructive">{{ fieldErrors.plan_id[0] }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <Label for="prov-cycle">Cycle</Label>
                                <Select v-model="provisionForm.billing_cycle">
                                    <SelectTrigger id="prov-cycle" class="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="monthly">Monthly</SelectItem>
                                        <SelectItem value="annual">Annual</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-2">
                                <Label for="prov-grace">Grace days</Label>
                                <Input
                                    id="prov-grace"
                                    type="number"
                                    :model-value="provisionForm.grace_days === null ? '' : String(provisionForm.grace_days)"
                                    @update:model-value="provisionForm.grace_days = $event === '' ? null : Number($event)"
                                />
                            </div>
                            <div class="flex items-end gap-2 pb-2">
                                <Switch :checked="provisionForm.auto_renewal" @update:checked="provisionForm.auto_renewal = $event" />
                                <Label>Auto-renew</Label>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="provisionOpen = false">Cancel</Button>
                            <Button type="submit" :disabled="saving">{{ saving ? 'Provisioning…' : 'Provision' }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="actionOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{ actionKind }} subscription</DialogTitle>
                        <DialogDescription>{{ actionTarget?.subscription_code }} · {{ actionTarget?.tenant?.name }}</DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="confirmAction">
                        <div v-if="actionKind !== 'renew'" class="space-y-2">
                            <Label for="action-reason">Reason (optional)</Label>
                            <Input id="action-reason" v-model="actionReason" placeholder="e.g. Payment failure" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="actionOpen = false">Cancel</Button>
                            <Button type="submit" :variant="actionKind === 'cancel' || actionKind === 'suspend' ? 'destructive' : 'default'">
                                {{ actionKind }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="changeOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Change plan</DialogTitle>
                        <DialogDescription>{{ changeTarget?.subscription_code }} · {{ changeTarget?.tenant?.name }}</DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="confirmChange">
                        <div class="space-y-2">
                            <Label for="change-plan">New plan</Label>
                            <Select v-model="changePlanId">
                                <SelectTrigger id="change-plan" class="w-full"><SelectValue placeholder="Select plan" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="plan in plans" :key="plan.id" :value="String(plan.id)">{{ plan.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="change-reason">Reason (optional)</Label>
                            <Input id="change-reason" v-model="changeReason" placeholder="e.g. Upgrade to Enterprise" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="changeOpen = false">Cancel</Button>
                            <Button type="submit" :disabled="!changePlanId">Change plan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="featureOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Feature overrides</DialogTitle>
                        <DialogDescription>{{ featureTarget?.subscription_code }} · {{ featureTarget?.tenant?.name }}</DialogDescription>
                    </DialogHeader>
                    <div v-if="featureTarget" class="grid grid-cols-2 gap-2">
                        <div
                            v-for="override in featureTarget.features"
                            :key="override.feature_code"
                            class="flex items-center justify-between gap-2 rounded-lg border p-3"
                        >
                            <Label class="cursor-pointer text-sm">{{ override.feature_code.replace(/-/g, ' ') }}</Label>
                            <Switch :checked="override.is_enabled" @update:checked="toggleFeature(override.feature_code, $event)" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="featureOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="historyOpen">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>History</DialogTitle>
                        <DialogDescription>{{ historyTarget?.subscription_code }}</DialogDescription>
                    </DialogHeader>
                    <div class="max-h-96 overflow-y-auto pr-3">
                        <div v-if="history.length" class="space-y-3">
                            <div v-for="entry in history" :key="entry.id" class="rounded-lg border p-3">
                                <div class="flex items-center justify-between">
                                    <Badge variant="secondary">{{ entry.action }}</Badge>
                                    <span class="text-xs text-muted-foreground">{{ new Date(entry.created_at).toLocaleString() }}</span>
                                </div>
                                <p class="mt-2 text-sm">{{ entry.description }}</p>
                            </div>
                        </div>
                        <p v-else class="py-6 text-center text-sm text-muted-foreground">No history recorded.</p>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="historyOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="licenseOpen">
                <DialogContent class="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Licenses</DialogTitle>
                        <DialogDescription>{{ licenseTarget?.subscription_code }} · {{ licenseTarget?.tenant?.name }}</DialogDescription>
                    </DialogHeader>
                    <div v-if="licenseLoading" class="space-y-2">
                        <Skeleton v-for="i in 3" :key="i" class="h-12" />
                    </div>
                    <div v-else-if="licenses.length" class="max-h-96 overflow-y-auto pr-2">
                        <div v-for="license in licenses" :key="license.id" class="mb-3 rounded-lg border p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate font-mono text-xs">{{ license.revealed ? license.license_key : license.masked_key }}</span>
                                        <Badge :variant="license.status === 'active' ? 'default' : 'outline'">{{ license.status }}</Badge>
                                    </div>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{ license.plan?.name ?? `Plan #${license.plan_id}` }} · Expires {{ license.expiration_date ?? '—' }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <Button variant="ghost" size="sm" @click="copyKey(license.revealed ? license.license_key : license.masked_key)">
                                        Copy
                                    </Button>
                                    <Button variant="ghost" size="sm" @click="revealLicense(license)">
                                        <Eye class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="license.status === 'active'"
                                        variant="ghost"
                                        size="sm"
                                        aria-label="Regenerate"
                                        @click="openLicenseAction('regenerate', license)"
                                    >
                                        <RefreshCw class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="license.status === 'active'"
                                        variant="ghost"
                                        size="sm"
                                        aria-label="Revoke"
                                        class="text-destructive"
                                        @click="openLicenseAction('revoke', license)"
                                    >
                                        <ShieldOff class="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="py-6 text-center text-sm text-muted-foreground">No license issued for this tenant.</p>
                    <DialogFooter>
                        <Button variant="outline" @click="licenseOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="licenseActionOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{ licenseAction === 'regenerate' ? 'Regenerate license' : 'Revoke license' }}</DialogTitle>
                        <DialogDescription>
                            {{ licenseActionTarget?.masked_key ?? '' }} ·
                            {{ licenseAction === 'regenerate' ? 'The previous key will stop working.' : 'The license will be permanently revoked.' }}
                        </DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="confirmLicenseAction">
                        <div class="space-y-2">
                            <Label for="license-reason">Reason (optional)</Label>
                            <Input id="license-reason" v-model="licenseReason" placeholder="e.g. Lost key" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="licenseActionTarget = null">Cancel</Button>
                            <Button type="submit" :variant="licenseAction === 'revoke' ? 'destructive' : 'default'">
                                {{ licenseAction === 'regenerate' ? 'Regenerate' : 'Revoke' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
