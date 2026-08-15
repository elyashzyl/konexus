<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { extractError } from '@/lib/api';
import { subscriptionApi, type SubscriptionDashboard } from '@/lib/subscriptionApi';
import { Activity, Building2, CreditCard, Globe, RadioTower, TriangleAlert } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

const router = useRouter();

const loading = ref(true);
const data = ref<SubscriptionDashboard | null>(null);

const money = (value: number | undefined) =>
    `₱${Number(value ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const statusLabel: Record<string, { label: string; variant: 'secondary' | 'default' | 'destructive' | 'outline' }> = {
    trial: { label: 'Trial', variant: 'secondary' },
    active: { label: 'Active', variant: 'default' },
    grace_period: { label: 'Grace', variant: 'outline' },
    past_due: { label: 'Past due', variant: 'outline' },
    suspended: { label: 'Suspended', variant: 'destructive' },
    cancelled: { label: 'Cancelled', variant: 'outline' },
    expired: { label: 'Expired', variant: 'destructive' },
};

onMounted(async () => {
    try {
        data.value = await subscriptionApi.dashboard();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

const metricCards = (): { label: string; value: string; icon: typeof Building2; tone: string }[] => {
    const m = data.value?.metrics ?? {};
    return [
        { label: 'Total tenants', value: String(m.total_tenants ?? 0), icon: Globe, tone: 'text-primary' },
        { label: 'Active tenants', value: String(m.active_tenants ?? 0), icon: Building2, tone: 'text-emerald-500' },
        { label: 'Active subscriptions', value: String(m.active_subscriptions ?? 0), icon: RadioTower, tone: 'text-primary' },
        { label: 'Trial subscriptions', value: String(m.trial_subscriptions ?? 0), icon: Activity, tone: 'text-amber-500' },
        { label: 'Expiring soon', value: String(m.expiring_subscriptions ?? 0), icon: TriangleAlert, tone: 'text-orange-500' },
        { label: 'Overdue invoices', value: String(m.overdue_invoices ?? 0), icon: CreditCard, tone: 'text-destructive' },
    ];
};
</script>

<template>
    <div class="relative">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12">
            <AdminPageHeader
                index="01"
                eyebrow="Platform"
                title="Subscription Overview"
                description="Revenue, tenant health and subscription lifecycle at a glance."
            />

            <section v-if="loading" class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Skeleton v-for="i in 6" :key="i" class="h-28" />
            </section>

            <section v-else class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="card in metricCards()" :key="card.label" class="border-border/60 bg-card/60">
                    <CardContent class="flex items-center justify-between p-6">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ card.label }}</p>
                            <p class="mt-2 font-display text-3xl font-medium tracking-[-0.01em]">{{ card.value }}</p>
                        </div>
                        <component :is="card.icon" :class="card.tone" class="size-8" />
                    </CardContent>
                </Card>
            </section>

            <section class="mt-8 grid gap-6 lg:grid-cols-5">
                <Card class="border-border/60 bg-card/60 lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="font-display text-lg font-medium">Revenue</CardTitle>
                        <CardDescription>This month vs. outstanding</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-baseline justify-between">
                            <span class="text-sm text-muted-foreground">Collected this month</span>
                            <span class="font-display text-xl font-medium">{{ money(data?.metrics.revenue_this_month) }}</span>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-sm text-muted-foreground">Unpaid balance</span>
                            <span class="font-display text-xl font-medium text-destructive">{{ money(data?.metrics.revenue_unpaid) }}</span>
                        </div>
                        <div class="pt-2">
                            <p class="mb-2 text-sm font-medium text-foreground">Plan breakdown</p>
                            <div v-if="data && Object.keys(data.plan_breakdown).length" class="space-y-2">
                                <div v-for="(count, plan) in data.plan_breakdown" :key="plan" class="flex items-center justify-between text-sm">
                                    <span class="text-muted-foreground">{{ plan }}</span>
                                    <Badge variant="secondary">{{ count }}</Badge>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">No active subscriptions yet.</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/60 bg-card/60 lg:col-span-3">
                    <CardHeader>
                        <CardTitle class="font-display text-lg font-medium">Expiring soon</CardTitle>
                        <CardDescription>Subscriptions within the notice window</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="data && data.expiring_soon.length" class="divide-y divide-border/60">
                            <div v-for="item in data.expiring_soon" :key="item.id" class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ item.tenant?.name ?? item.subscription_code }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ item.plan?.name ?? 'No plan' }} · expires {{ item.expiration_date }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <Badge :variant="statusLabel[item.status]?.variant ?? 'secondary'">{{
                                        statusLabel[item.status]?.label ?? item.status
                                    }}</Badge>
                                    <span class="text-xs text-muted-foreground">{{ item.days_remaining }}d left</span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="py-6 text-center text-sm text-muted-foreground">Nothing expiring in the notice window.</p>
                    </CardContent>
                </Card>
            </section>

            <section class="mt-6">
                <Card class="border-border/60 bg-card/60">
                    <CardHeader>
                        <CardTitle class="font-display text-lg font-medium">Recent activity</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="data && data.recent_activity.length" class="divide-y divide-border/60">
                            <div v-for="entry in data.recent_activity" :key="entry.id" class="flex items-start justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">{{ entry.description }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ entry.tenant?.name ?? 'Platform' }} · {{ new Date(entry.created_at).toLocaleString() }}
                                    </p>
                                </div>
                                <Badge variant="outline">{{ entry.action }}</Badge>
                            </div>
                        </div>
                        <p v-else class="py-6 text-center text-sm text-muted-foreground">No activity recorded yet.</p>
                        <div class="mt-2 flex justify-end">
                            <Button variant="outline" size="sm" @click="router.push('/admin/activity?source=subscription')">Open audit trail</Button>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </div>
    </div>
</template>
