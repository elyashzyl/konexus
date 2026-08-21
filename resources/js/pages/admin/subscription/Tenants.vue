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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { extractError, extractFieldErrors } from '@/lib/api';
import {
    subscriptionApi,
    type LicenseItem,
    type SubscriptionItem,
    type TenantItem,
    type TenantUsage,
} from '@/lib/subscriptionApi';
import {
    Ban,
    Building2,
    Check,
    Copy,
    Eye,
    EyeOff,
    KeyRound,
    Pause,
    Play,
    RefreshCw,
    Settings2,
    Trash2,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const saving = ref(false);
const items = ref<TenantItem[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = 15;
const search = ref('');
const statusFilter = ref<'all' | string>('all');
const fieldErrors = ref<Record<string, string[]>>({});

const dialogOpen = ref(false);
const editing = ref<TenantItem | null>(null);
const form = ref<{ name: string; code: string; status: string }>({ name: '', code: '', status: 'active' });

const actionDialogOpen = ref(false);
const actionKind = ref<'suspend' | 'resume'>('suspend');
const actionTarget = ref<TenantItem | null>(null);
const actionReason = ref('');

// ── Consolidated "Manage tenant" dialog ──────────────────────────────
const manageOpen = ref(false);
const manageLoading = ref(false);
const manageTenant = ref<TenantItem | null>(null);
const currentSubscription = ref<SubscriptionItem | null>(null);
const licenses = ref<LicenseItem[]>([]);
const usageDetail = ref<TenantUsage | null>(null);
const revealedLicenses = ref<Record<number, boolean>>({});
const busyLicenseId = ref<number | null>(null);
const busySubscription = ref(false);

async function openManage(tenant: TenantItem): Promise<void> {
    manageTenant.value = tenant;
    editing.value = tenant;
    form.value = { name: tenant.name, code: tenant.code, status: tenant.status };
    fieldErrors.value = {};
    currentSubscription.value = null;
    licenses.value = [];
    usageDetail.value = null;
    manageOpen.value = true;
    await loadManageData(tenant.id);
}

async function loadManageData(tenantId: number): Promise<void> {
    manageLoading.value = true;
    try {
        const [subs, keys, usage] = await Promise.all([
            subscriptionApi.subscriptions.index({ tenant_id: tenantId, per_page: 10 }),
            subscriptionApi.licenses.index({ tenant_id: tenantId, per_page: 10 }),
            subscriptionApi.usage.tenant(tenantId).catch(() => null),
        ]);
        currentSubscription.value = subs.items[0] ?? null;
        licenses.value = keys.items;
        usageDetail.value = usage;
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        manageLoading.value = false;
    }
}

async function saveDetails(): Promise<void> {
    if (!editing.value) return;
    saving.value = true;
    fieldErrors.value = {};
    try {
        const updated = await subscriptionApi.tenants.update(editing.value.id, {
            name: form.value.name,
            ...(form.value.code ? { code: form.value.code } : {}),
            status: form.value.status,
        });
        toast.success(`Tenant "${updated.name}" updated.`);
        manageTenant.value = updated;
        await refresh();
    } catch (error) {
        fieldErrors.value = extractFieldErrors(error);
        toast.error(extractError(error));
    } finally {
        saving.value = false;
    }
}

async function subscriptionAction(kind: 'renew' | 'suspend' | 'resume' | 'cancel'): Promise<void> {
    const subscription = currentSubscription.value;
    if (!subscription) return;

    if (kind === 'cancel' && !window.confirm('Cancel this subscription? The school loses access at the end of the period.')) return;
    if (kind === 'suspend' && !window.confirm('Suspend this subscription? The school cannot use gated modules until resumed.')) return;

    busySubscription.value = true;
    try {
        if (kind === 'renew') currentSubscription.value = await subscriptionApi.subscriptions.renew(subscription.id);
        if (kind === 'suspend') currentSubscription.value = await subscriptionApi.subscriptions.suspend(subscription.id);
        if (kind === 'resume') currentSubscription.value = await subscriptionApi.subscriptions.resume(subscription.id);
        if (kind === 'cancel') currentSubscription.value = await subscriptionApi.subscriptions.cancel(subscription.id);
        toast.success('Subscription updated.');
        await refresh();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        busySubscription.value = false;
    }
}

async function revealLicense(license: LicenseItem): Promise<void> {
    if (revealedLicenses.value[license.id]) {
        revealedLicenses.value[license.id] = false;
        return;
    }
    try {
        const full = await subscriptionApi.licenses.show(license.id, true);
        licenses.value = licenses.value.map((l) => (l.id === license.id ? full : l));
        revealedLicenses.value[license.id] = true;
    } catch (error) {
        toast.error(extractError(error));
    }
}

async function copyLicense(license: LicenseItem): Promise<void> {
    try {
        let key = license.masked_key;
        if (revealedLicenses.value[license.id]) {
            key = license.license_key;
        } else {
            const full = await subscriptionApi.licenses.show(license.id, true);
            key = full.license_key;
            licenses.value = licenses.value.map((l) => (l.id === license.id ? full : l));
            revealedLicenses.value[license.id] = true;
        }
        await navigator.clipboard.writeText(key);
        toast.success('License key copied to clipboard.');
    } catch (error) {
        toast.error(extractError(error));
    }
}

async function regenerateLicense(license: LicenseItem): Promise<void> {
    if (!window.confirm('Regenerate this license key? The previous key stops working immediately.')) return;
    busyLicenseId.value = license.id;
    try {
        const fresh = await subscriptionApi.licenses.regenerate(license.id);
        licenses.value = licenses.value.map((l) => (l.id === license.id ? fresh : l));
        revealedLicenses.value[license.id] = false;
        toast.success('License key regenerated.');
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        busyLicenseId.value = null;
    }
}

async function revokeLicense(license: LicenseItem): Promise<void> {
    if (!window.confirm('Revoke this license? The school loses license-based access.')) return;
    busyLicenseId.value = license.id;
    try {
        const revoked = await subscriptionApi.licenses.revoke(license.id);
        licenses.value = licenses.value.map((l) => (l.id === license.id ? revoked : l));
        toast.success('License revoked.');
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        busyLicenseId.value = null;
    }
}

function openAction(kind: 'suspend' | 'resume', tenant: TenantItem): void {
    actionKind.value = kind;
    actionTarget.value = tenant;
    actionReason.value = '';
    actionDialogOpen.value = true;
}

async function confirmAction(): Promise<void> {
    if (!actionTarget.value) return;
    try {
        if (actionKind.value === 'suspend') {
            await subscriptionApi.tenants.suspend(actionTarget.value.id, actionReason.value || undefined);
            toast.success(`Tenant "${actionTarget.value.name}" suspended.`);
        } else {
            await subscriptionApi.tenants.resume(actionTarget.value.id, actionReason.value || undefined);
            toast.success(`Tenant "${actionTarget.value.name}" reactivated.`);
        }
        actionDialogOpen.value = false;
        await refresh();
    } catch (error) {
        toast.error(extractError(error));
    }
}

async function remove(tenant: TenantItem): Promise<void> {
    if (!window.confirm(`Delete tenant "${tenant.name}"? This cannot be undone.`)) return;
    try {
        await subscriptionApi.tenants.destroy(tenant.id);
        toast.success('Tenant deleted.');
        await refresh();
    } catch (error) {
        toast.error(extractError(error));
    }
}

onMounted(refresh);

async function refresh(): Promise<void> {
    loading.value = true;
    try {
        const data = await subscriptionApi.tenants.index({
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

const lastPage = computed(() => Math.max(1, Math.ceil(total.value / perPage)));

const statusTone = (status: string | null | undefined) => {
    switch (status) {
        case 'active':
        case 'trial':
            return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
        case 'grace_period':
        case 'past_due':
        case 'suspended':
            return 'border-orange-500/30 bg-orange-500/10 text-orange-700 dark:text-orange-400';
        case 'expired':
        case 'cancelled':
        case 'revoked':
        case 'deactivated':
            return 'border-destructive/30 bg-destructive/10 text-destructive';
        default:
            return '';
    }
};

const usagePct = (used: number, limit: number | null | undefined) => (limit ? Math.min(100, Math.round((used / limit) * 100)) : 0);
const usageBarTone = (used: number, limit: number | null | undefined) => {
    const p = usagePct(used, limit);
    if (p >= 100) return 'bg-destructive';
    if (p >= 80) return 'bg-orange-500';
    return 'bg-primary';
};

interface UsageCard {
    label: string;
    used: number;
    limit: number | null;
    suffix?: string;
}

const usageCards = computed<UsageCard[]>(() => {
    const snapshot = usageDetail.value?.snapshot;
    const limits = usageDetail.value?.limit_status.limits ?? {};
    if (!snapshot) return [];
    return [
        { label: 'Students', used: snapshot.students_count, limit: limits.max_students ?? null },
        { label: 'User accounts', used: snapshot.users_count, limit: limits.max_users ?? null },
        { label: 'Campuses', used: snapshot.branches_count, limit: limits.max_branches ?? null },
        { label: 'Storage', used: snapshot.storage_mb, limit: limits.max_storage_mb ?? null, suffix: 'MB' },
    ];
});
</script>

<template>
    <div class="relative">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="Building2"
                index="02"
                eyebrow="Platform"
                title="Tenants"
                description="Every tenant corresponds to a school registered on the platform."
            >
            </AdminPageHeader>

            <section class="portal-rise mt-10">
                <div class="flex flex-wrap items-center gap-2">
                    <Input
                        v-model="search"
                        placeholder="Search tenants…"
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
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="suspended">Suspended</SelectItem>
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
                    <div class="ml-auto text-sm text-muted-foreground">{{ total }} tenants</div>
                </div>
            </section>

            <section class="portal-rise mt-6" style="animation-delay: 120ms">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader>
                        <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">Tenants</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="loading" class="space-y-2">
                            <Skeleton v-for="i in 6" :key="i" class="h-12" />
                        </div>

                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tenant</TableHead>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Subscriptions</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="tenant in items" :key="tenant.id">
                                    <TableCell>
                                        <div class="font-medium">{{ tenant.name }}</div>
                                        <div v-if="tenant.school_profile" class="text-xs text-muted-foreground">{{ tenant.school_profile.name }}</div>
                                    </TableCell>
                                    <TableCell class="font-mono text-xs">{{ tenant.code }}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" :class="statusTone(tenant.status)">{{ tenant.status }}</Badge>
                                    </TableCell>
                                    <TableCell class="text-sm">{{ tenant.subscription_count ?? 0 }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button variant="ghost" size="icon" aria-label="Manage" @click="openManage(tenant)">
                                                <Settings2 class="size-4" />
                                            </Button>
                                            <Button
                                                v-if="tenant.status !== 'suspended'"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Suspend"
                                                @click="openAction('suspend', tenant)"
                                            >
                                                <Pause class="size-4" />
                                            </Button>
                                            <Button
                                                v-if="tenant.status === 'suspended'"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Resume"
                                                @click="openAction('resume', tenant)"
                                            >
                                                <Play class="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" aria-label="Delete" class="text-destructive" @click="remove(tenant)">
                                                <Trash2 class="size-4" />
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

            <!-- Consolidated tenant management -->
            <Dialog v-model:open="manageOpen">
                <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>Manage {{ manageTenant?.name }}</DialogTitle>
                        <DialogDescription>Everything about this school's account in one place.</DialogDescription>
                    </DialogHeader>

                    <div v-if="manageLoading" class="space-y-2">
                        <Skeleton v-for="i in 5" :key="i" class="h-16" />
                    </div>

                    <template v-else>
                        <!-- School details -->
                        <section class="rounded-xl border border-border/60 p-4">
                            <h3 class="text-sm font-semibold">School details</h3>
                            <p class="mt-0.5 text-xs text-muted-foreground">The name and code shown across the platform.</p>
                            <form class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3" @submit.prevent="saveDetails">
                                <div class="space-y-2">
                                    <Label for="tenant-name">Name</Label>
                                    <Input id="tenant-name" v-model="form.name" placeholder="Maple Grove Academy" />
                                    <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name[0] }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="tenant-code">Code</Label>
                                    <Input id="tenant-code" v-model="form.code" placeholder="MG-ACAD" />
                                    <p v-if="fieldErrors.code" class="text-xs text-destructive">{{ fieldErrors.code[0] }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="tenant-status">Status</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger id="tenant-status">
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="suspended">Suspended</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="sm:col-span-3 flex items-center justify-between gap-3">
                                    <p class="text-xs text-muted-foreground">
                                        Linked school:
                                        <span class="font-medium text-foreground">{{ manageTenant?.school_profile?.name ?? 'None' }}</span>
                                    </p>
                                    <Button type="submit" size="sm" :disabled="saving">
                                        <Check class="mr-1 size-4" />{{ saving ? 'Saving…' : 'Save changes' }}
                                    </Button>
                                </div>
                            </form>
                        </section>

                        <!-- Subscription -->
                        <section class="rounded-xl border border-border/60 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold">Subscription</h3>
                                    <p class="mt-0.5 text-xs text-muted-foreground">The plan this school is currently billed under.</p>
                                </div>
                                <Badge v-if="currentSubscription" variant="outline" :class="statusTone(currentSubscription.status)">
                                    {{ currentSubscription.status.replace('_', ' ') }}
                                </Badge>
                            </div>

                            <template v-if="currentSubscription">
                                <div class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                    <div>
                                        <p class="text-xs text-muted-foreground">Plan</p>
                                        <p class="font-medium">{{ currentSubscription.plan?.name ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Billing</p>
                                        <p class="font-medium capitalize">{{ currentSubscription.billing_cycle }} · ₱{{ currentSubscription.amount }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Expires</p>
                                        <p class="font-medium">{{ currentSubscription.expiration_date ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Days remaining</p>
                                        <p class="font-medium" :class="currentSubscription.days_remaining <= 15 ? 'text-orange-600' : ''">
                                            {{ currentSubscription.days_remaining }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <Button variant="outline" size="sm" :disabled="busySubscription" @click="subscriptionAction('renew')">Renew</Button>
                                    <Button
                                        v-if="currentSubscription.status !== 'suspended'"
                                        variant="outline"
                                        size="sm"
                                        :disabled="busySubscription"
                                        @click="subscriptionAction('suspend')"
                                        >Suspend</Button
                                    >
                                    <Button
                                        v-if="currentSubscription.status === 'suspended'"
                                        variant="outline"
                                        size="sm"
                                        :disabled="busySubscription"
                                        @click="subscriptionAction('resume')"
                                        >Resume</Button
                                    >
                                    <Button variant="ghost" size="sm" class="text-destructive" :disabled="busySubscription" @click="subscriptionAction('cancel')">
                                        Cancel subscription
                                    </Button>
                                </div>
                            </template>
                            <p v-else class="mt-4 rounded-lg bg-muted/50 p-3 text-sm text-muted-foreground">
                                No subscription yet. Provision one from the Subscriptions page to activate this school.
                            </p>
                        </section>

                        <!-- License -->
                        <section class="rounded-xl border border-border/60 p-4">
                            <div class="flex items-center gap-2">
                                <KeyRound class="size-4 text-primary" />
                                <h3 class="text-sm font-semibold">License key</h3>
                            </div>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Limits below are enforced when the school creates students, staff, users or campuses.
                            </p>

                            <template v-if="licenses.length">
                                <div v-for="license in licenses" :key="license.id" class="mt-4 space-y-3 rounded-lg bg-muted/40 p-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <code class="rounded bg-background px-2 py-1 font-mono text-xs">{{ license.masked_key }}</code>
                                        <Badge variant="outline" :class="statusTone(license.status)">{{ license.status }}</Badge>
                                        <span v-if="license.expiration_date" class="text-xs text-muted-foreground">Expires {{ license.expiration_date }}</span>
                                        <div class="ml-auto flex gap-1">
                                            <Button variant="ghost" size="icon" aria-label="Reveal" @click="revealLicense(license)">
                                                <Eye v-if="!revealedLicenses[license.id]" class="size-4" />
                                                <EyeOff v-else class="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" aria-label="Copy" @click="copyLicense(license)">
                                                <Copy class="size-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Regenerate"
                                                :disabled="busyLicenseId === license.id || license.status !== 'active'"
                                                @click="regenerateLicense(license)"
                                            >
                                                <RefreshCw class="size-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Revoke"
                                                class="text-destructive"
                                                :disabled="busyLicenseId === license.id || license.status !== 'active'"
                                                @click="revokeLicense(license)"
                                            >
                                                <Ban class="size-4" />
                                            </Button>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-full border border-border/60 px-2 py-0.5">Students: {{ license.max_students ?? '∞' }}</span>
                                        <span class="rounded-full border border-border/60 px-2 py-0.5">Users: {{ license.max_users ?? '∞' }}</span>
                                        <span class="rounded-full border border-border/60 px-2 py-0.5">Campuses: {{ license.max_branches ?? '∞' }}</span>
                                        <span class="rounded-full border border-border/60 px-2 py-0.5">Storage: {{ license.max_storage_mb ?? '∞' }} MB</span>
                                    </div>
                                </div>
                            </template>
                            <p v-else class="mt-4 rounded-lg bg-muted/50 p-3 text-sm text-muted-foreground">
                                No license issued yet. Licenses are issued automatically when provisioning a subscription with "Issue license" enabled.
                            </p>
                        </section>

                        <!-- Live usage -->
                        <section class="rounded-xl border border-border/60 p-4">
                            <h3 class="text-sm font-semibold">Live usage</h3>
                            <p class="mt-0.5 text-xs text-muted-foreground">Current consumption against the enforced limits.</p>

                            <div v-if="usageCards.length" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <Card v-for="card in usageCards" :key="card.label" class="border-border/60 bg-card/60">
                                    <CardHeader><CardTitle class="font-display text-sm font-medium">{{ card.label }}</CardTitle></CardHeader>
                                    <CardContent>
                                        <p class="font-display text-2xl font-medium">{{ card.used }}<span v-if="card.suffix" class="text-sm"> {{ card.suffix }}</span></p>
                                        <p class="text-xs text-muted-foreground">Limit {{ card.limit ?? '∞' }}{{ card.suffix ? ' ' + card.suffix : '' }}</p>
                                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-muted">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="usageBarTone(card.used, card.limit)"
                                                :style="{ width: `${usagePct(card.used, card.limit)}%` }"
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                            <p v-else class="mt-4 rounded-lg bg-muted/50 p-3 text-sm text-muted-foreground">No usage snapshot available yet.</p>

                            <div v-if="usageDetail?.limit_status.warnings.length" class="mt-3 space-y-2">
                                <div
                                    v-for="warning in usageDetail.limit_status.warnings"
                                    :key="warning.key"
                                    class="flex items-center justify-between rounded-lg border border-orange-500/30 bg-orange-500/5 p-3"
                                >
                                    <div>
                                        <p class="text-sm font-medium">{{ warning.label }}</p>
                                        <p class="text-xs text-muted-foreground">{{ warning.used }} of {{ warning.limit }} used ({{ warning.percent }}%)</p>
                                    </div>
                                    <Badge variant="outline">At limit</Badge>
                                </div>
                            </div>
                        </section>
                    </template>

                    <DialogFooter>
                        <Button variant="outline" @click="manageOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="actionDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{ actionKind === 'suspend' ? `Suspend ${actionTarget?.name}` : `Resume ${actionTarget?.name}` }}</DialogTitle>
                        <DialogDescription>
                            {{
                                actionKind === 'suspend'
                                    ? 'The tenant and its active subscriptions will be suspended.'
                                    : 'The tenant and its subscriptions will be reactivated.'
                            }}
                        </DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="confirmAction">
                        <div class="space-y-2">
                            <Label for="action-reason">Reason (optional)</Label>
                            <Input id="action-reason" v-model="actionReason" placeholder="e.g. Payment failure" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="actionDialogOpen = false">Cancel</Button>
                            <Button type="submit">{{ actionKind === 'suspend' ? 'Suspend' : 'Resume' }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
