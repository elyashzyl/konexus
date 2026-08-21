<script setup lang="ts">
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import api, { extractError } from '@/lib/api';
import { KeyRound, RefreshCw, Wallet } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

interface SchoolSubscriptionSummary {
    tenant: { id: number; name: string; code: string; status: string } | null;
    subscription: {
        subscription_code: string;
        status: string;
        billing_cycle: string;
        amount: string | number;
        start_date: string | null;
        expiration_date: string | null;
        days_remaining: number;
        auto_renewal: boolean;
        allows_access: boolean;
        plan: { id: number; name: string; code: string; billing_cycle: string } | null;
        features?: { feature_code: string; is_enabled: boolean }[];
    } | null;
    features: string[];
    limits: Record<string, number | null>;
    usage: {
        usage: Record<string, number>;
        limits: Record<string, number | null>;
        warnings: { key: string; label: string; used: number; limit: number; percent: number }[];
    };
    license: { masked_key: string; status: string; expiration_date: string | null } | null;
    message?: string;
}

const loading = ref(true);
const summary = ref<SchoolSubscriptionSummary | null>(null);

async function refresh(): Promise<void> {
    loading.value = true;
    try {
        const { data } = await api.get<{ data: SchoolSubscriptionSummary }>('/subscription/mine');
        summary.value = data.data;
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
}

onMounted(refresh);

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
    const usage = summary.value?.usage?.usage ?? {};
    const limits = summary.value?.usage?.limits ?? {};
    return [
        { label: 'Students', used: Number(usage.students_count ?? 0), limit: limits.max_students ?? null },
        { label: 'Staff', used: Number(usage.staff_count ?? 0), limit: limits.max_staff ?? null },
        { label: 'User accounts', used: Number(usage.users_count ?? 0), limit: limits.max_users ?? null },
        { label: 'Campuses', used: Number(usage.branches_count ?? 0), limit: limits.max_branches ?? null },
        { label: 'Storage', used: Number(usage.storage_mb ?? 0), limit: limits.max_storage_mb ?? null, suffix: 'MB' },
    ];
});

const featureLabels = computed(() => {
    const overrides = summary.value?.subscription?.features ?? [];
    const overrideMap = new Map(overrides.map((override) => [override.feature_code, override.is_enabled]));
    return (summary.value?.features ?? []).map((code) => ({
        code,
        enabled: overrideMap.get(code) ?? true,
    }));
});
</script>

<template>
    <div class="relative">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12">
            <PortalPageHeader
                :icon="Wallet"
                eyebrow="Finance office"
                title="Subscription & billing"
                description="Your school's plan, enforced license limits and live usage at a glance."
            >
                <template #actions>
                    <Button variant="outline" size="sm" :disabled="loading" @click="refresh">
                        <RefreshCw class="mr-1 size-4" />Refresh
                    </Button>
                </template>
            </PortalPageHeader>

            <div v-if="loading" class="portal-rise mt-10 space-y-2">
                <Skeleton v-for="i in 4" :key="i" class="h-24" />
            </div>

            <template v-else-if="summary">
                <p v-if="!summary.tenant" class="portal-rise mt-10 rounded-2xl border border-border/60 bg-card/50 p-8 text-center text-sm text-muted-foreground">
                    {{ summary.message ?? 'No school subscription is linked to this account.' }}
                </p>

                <template v-else>
                    <!-- Plan overview -->
                    <section class="portal-rise mt-10 grid gap-4 lg:grid-cols-3">
                        <Card class="border-border/60 bg-card/60 lg:col-span-2">
                            <CardHeader>
                                <CardTitle class="font-display text-base font-medium">Current plan</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <template v-if="summary.subscription">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <p class="font-display text-2xl font-medium">{{ summary.subscription.plan?.name ?? 'Custom plan' }}</p>
                                        <Badge variant="outline" :class="statusTone(summary.subscription.status)">
                                            {{ summary.subscription.status.replace('_', ' ') }}
                                        </Badge>
                                        <Badge v-if="!summary.subscription.allows_access" variant="outline" class="border-destructive/30 bg-destructive/10 text-destructive">
                            Access paused
                                        </Badge>
                                    </div>
                                    <div class="mt-5 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                                        <div>
                                            <p class="text-xs text-muted-foreground">Billing cycle</p>
                                            <p class="font-medium capitalize">{{ summary.subscription.billing_cycle }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Amount</p>
                                            <p class="font-medium">₱{{ summary.subscription.amount }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Renews / expires</p>
                                            <p class="font-medium">{{ summary.subscription.expiration_date ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Days remaining</p>
                                            <p class="font-medium" :class="summary.subscription.days_remaining <= 15 ? 'text-orange-600' : ''">
                                                {{ summary.subscription.days_remaining }}
                                            </p>
                                        </div>
                                    </div>
                                </template>
                                <p v-else class="text-sm text-muted-foreground">
                                    No active subscription. Contact the school administrator or the platform team to activate this school.
                                </p>
                            </CardContent>
                        </Card>

                        <Card class="border-border/60 bg-card/60">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2 font-display text-base font-medium">
                                    <KeyRound class="size-4 text-primary" /> License
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <template v-if="summary.license">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <code class="rounded bg-muted px-2 py-1 font-mono text-xs">{{ summary.license.masked_key }}</code>
                                        <Badge variant="outline" :class="statusTone(summary.license.status)">{{ summary.license.status }}</Badge>
                                    </div>
                                    <p class="mt-3 text-xs text-muted-foreground">
                                        Expires {{ summary.license.expiration_date ?? 'never' }} · limits below are enforced when creating records.
                                    </p>
                                </template>
                                <p v-else class="text-sm text-muted-foreground">No license issued for this school yet.</p>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- Usage vs limits -->
                    <section class="portal-rise mt-6" style="animation-delay: 100ms">
                        <h2 class="font-display text-lg font-medium tracking-[-0.01em]">Usage against limits</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
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

                        <div v-if="summary.usage.warnings.length" class="mt-4 space-y-2">
                            <div
                                v-for="warning in summary.usage.warnings"
                                :key="warning.key"
                                class="flex items-center justify-between rounded-lg border border-orange-500/30 bg-orange-500/5 p-3"
                            >
                                <div>
                                    <p class="text-sm font-medium">{{ warning.label }}</p>
                                    <p class="text-xs text-muted-foreground">{{ warning.used }} of {{ warning.limit }} used ({{ warning.percent }}%)</p>
                                </div>
                                <Badge variant="outline">At limit</Badge>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                When a limit is reached, creating more records is blocked until the plan or license is upgraded.
                            </p>
                        </div>
                    </section>

                    <!-- Enabled features -->
                    <section v-if="featureLabels.length" class="portal-rise mt-6" style="animation-delay: 160ms">
                        <h2 class="font-display text-lg font-medium tracking-[-0.01em]">Included modules</h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                v-for="feature in featureLabels"
                                :key="feature.code"
                                class="rounded-full border border-border/60 bg-card/60 px-3 py-1 text-xs capitalize"
                                :class="{ 'opacity-50 line-through': !feature.enabled }"
                            >
                                {{ feature.code.replace(/[-_]/g, ' ') }}
                            </span>
                        </div>
                    </section>
                </template>
            </template>
        </div>
    </div>
</template>
