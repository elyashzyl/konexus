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
import { subscriptionApi, type PlanFeatureOption, type SubscriptionPlanItem } from '@/lib/subscriptionApi';
import { Boxes, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const saving = ref(false);
const items = ref<SubscriptionPlanItem[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = 15;
const search = ref('');
const fieldErrors = ref<Record<string, string[]>>({});

const featureCatalog = ref<PlanFeatureOption[]>([]);
const dialogOpen = ref(false);
const editing = ref<SubscriptionPlanItem | null>(null);
const form = ref<{
    name: string;
    code: string;
    description: string;
    billing_cycle: string;
    monthly_price: number;
    annual_price: number;
    trial_days: number | null;
    max_students: number | null;
    max_staff: number | null;
    max_branches: number | null;
    max_users: number | null;
    max_storage_mb: number | null;
    is_active: boolean;
    display_order: number;
    features: Record<string, boolean>;
}>({
    name: '',
    code: '',
    description: '',
    billing_cycle: 'monthly',
    monthly_price: 0,
    annual_price: 0,
    trial_days: null,
    max_students: null,
    max_staff: null,
    max_branches: null,
    max_users: null,
    max_storage_mb: null,
    is_active: true,
    display_order: 0,
    features: {},
});

onMounted(async () => {
    try {
        featureCatalog.value = await subscriptionApi.plans.featureCatalog();
    } catch (error) {
        toast.error(extractError(error));
    }
    await refresh();
});

async function refresh(): Promise<void> {
    loading.value = true;
    try {
        const data = await subscriptionApi.plans.index({ page: page.value, per_page: perPage, search: search.value || undefined });
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

const num = (v: string | number | null | undefined): number | null => {
    if (v === '' || v === null || v === undefined) return null;
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
};

function openCreate(): void {
    editing.value = null;
    form.value = {
        name: '',
        code: '',
        description: '',
        billing_cycle: 'monthly',
        monthly_price: 0,
        annual_price: 0,
        trial_days: null,
        max_students: null,
        max_staff: null,
        max_branches: null,
        max_users: null,
        max_storage_mb: null,
        is_active: true,
        display_order: 0,
        features: Object.fromEntries(featureCatalog.value.map((f) => [f.value, false])),
    };
    fieldErrors.value = {};
    dialogOpen.value = true;
}

function openEdit(plan: SubscriptionPlanItem): void {
    editing.value = plan;
    const featureSet = new Set(plan.features);
    form.value = {
        name: plan.name,
        code: plan.code,
        description: plan.description ?? '',
        billing_cycle: plan.billing_cycle,
        monthly_price: plan.monthly_price,
        annual_price: plan.annual_price,
        trial_days: plan.trial_days,
        max_students: plan.max_students,
        max_staff: plan.max_staff,
        max_branches: plan.max_branches,
        max_users: plan.max_users,
        max_storage_mb: plan.max_storage_mb,
        is_active: plan.is_active,
        display_order: plan.display_order,
        features: Object.fromEntries(featureCatalog.value.map((f) => [f.value, featureSet.has(f.value)])),
    };
    fieldErrors.value = {};
    dialogOpen.value = true;
}

async function save(): Promise<void> {
    saving.value = true;
    fieldErrors.value = {};
    try {
        const payload: Record<string, unknown> = {
            name: form.value.name,
            code: form.value.code,
            description: form.value.description || null,
            billing_cycle: form.value.billing_cycle,
            monthly_price: form.value.monthly_price,
            annual_price: form.value.annual_price,
            trial_days: form.value.trial_days,
            max_students: form.value.max_students,
            max_staff: form.value.max_staff,
            max_branches: form.value.max_branches,
            max_users: form.value.max_users,
            max_storage_mb: form.value.max_storage_mb,
            is_active: form.value.is_active,
            display_order: form.value.display_order,
            features: Object.entries(form.value.features)
                .filter(([, enabled]) => enabled)
                .map(([code]) => code),
        };

        if (editing.value) {
            await subscriptionApi.plans.update(editing.value.id, payload);
            toast.success(`Plan "${form.value.name}" updated.`);
        } else {
            await subscriptionApi.plans.store(payload);
            toast.success(`Plan "${form.value.name}" created.`);
        }
        dialogOpen.value = false;
        await refresh();
    } catch (error) {
        fieldErrors.value = extractFieldErrors(error);
        toast.error(extractError(error));
    } finally {
        saving.value = false;
    }
}

async function remove(plan: SubscriptionPlanItem): Promise<void> {
    if (!window.confirm(`Delete plan "${plan.name}"?`)) return;
    try {
        await subscriptionApi.plans.destroy(plan.id);
        toast.success('Plan deleted.');
        await refresh();
    } catch (error) {
        toast.error(extractError(error));
    }
}

function currency(v: number): string {
    return `₱${Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}
</script>

<template>
    <div class="relative">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="Boxes"
                index="03"
                eyebrow="Platform"
                title="Subscription Plans"
                description="Tiers, pricing and the module features each plan unlocks."
            >
                <template #actions>
                    <Button @click="openCreate"><Plus class="size-4" /> New plan</Button>
                </template>
            </AdminPageHeader>

            <section class="portal-rise mt-10 flex flex-wrap items-center gap-2">
                <Input
                    v-model="search"
                    placeholder="Search plans…"
                    class="w-56"
                    @keydown.enter="
                        page = 1;
                        refresh();
                    "
                />
                <Button
                    variant="ghost"
                    size="sm"
                    @click="
                        page = 1;
                        refresh();
                    "
                    >Apply</Button
                >
                <div class="ml-auto text-sm text-muted-foreground">{{ total }} plans</div>
            </section>

            <section class="portal-rise mt-6" style="animation-delay: 120ms">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader>
                        <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">Plans</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="loading" class="space-y-2">
                            <Skeleton v-for="i in 5" :key="i" class="h-12" />
                        </div>

                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Plan</TableHead>
                                    <TableHead>Billing</TableHead>
                                    <TableHead>Monthly</TableHead>
                                    <TableHead>Annual</TableHead>
                                    <TableHead>Trial</TableHead>
                                    <TableHead>Features</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="plan in items" :key="plan.id">
                                    <TableCell>
                                        <div class="font-medium">{{ plan.name }}</div>
                                        <div class="font-mono text-xs text-muted-foreground">{{ plan.code }}</div>
                                    </TableCell>
                                    <TableCell class="capitalize">{{ plan.billing_cycle }}</TableCell>
                                    <TableCell>{{ currency(plan.monthly_price) }}</TableCell>
                                    <TableCell>{{ currency(plan.annual_price) }}</TableCell>
                                    <TableCell>{{ plan.trial_days ?? '—' }}</TableCell>
                                    <TableCell>
                                        <div class="flex max-w-xs flex-wrap gap-1">
                                            <Badge v-for="feature in plan.features.slice(0, 3)" :key="feature" variant="secondary">{{
                                                feature
                                            }}</Badge>
                                            <Badge v-if="plan.features.length > 3" variant="outline">+{{ plan.features.length - 3 }}</Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="plan.is_active ? 'default' : 'outline'">{{ plan.is_active ? 'Active' : 'Inactive' }}</Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button variant="ghost" size="icon" aria-label="Edit" @click="openEdit(plan)">
                                                <Pencil class="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" aria-label="Delete" class="text-destructive" @click="remove(plan)">
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </section>

            <Dialog v-model:open="dialogOpen">
                <DialogContent class="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>{{ editing ? `Edit ${editing.name}` : 'New plan' }}</DialogTitle>
                        <DialogDescription>Define pricing, limits and the module features included.</DialogDescription>
                    </DialogHeader>
                    <form class="grid gap-5" @submit.prevent="save">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="plan-name">Name</Label>
                                <Input id="plan-name" v-model="form.name" placeholder="Standard" />
                                <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name[0] }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="plan-code">Code</Label>
                                <Input id="plan-code" v-model="form.code" placeholder="standard" />
                                <p v-if="fieldErrors.code" class="text-xs text-destructive">{{ fieldErrors.code[0] }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="plan-desc">Description</Label>
                            <Textarea id="plan-desc" v-model="form.description" placeholder="Short description shown on the plans page" />
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <Label for="plan-cycle">Billing cycle</Label>
                                <Select v-model="form.billing_cycle">
                                    <SelectTrigger id="plan-cycle" class="w-full"><SelectValue placeholder="Select cycle" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="monthly">Monthly</SelectItem>
                                        <SelectItem value="annual">Annual</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="fieldErrors.billing_cycle" class="text-xs text-destructive">{{ fieldErrors.billing_cycle[0] }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="plan-monthly">Monthly price</Label>
                                <Input
                                    id="plan-monthly"
                                    type="number"
                                    step="0.01"
                                    :model-value="String(form.monthly_price)"
                                    @update:model-value="form.monthly_price = Number($event) || 0"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="plan-annual">Annual price</Label>
                                <Input
                                    id="plan-annual"
                                    type="number"
                                    step="0.01"
                                    :model-value="String(form.annual_price)"
                                    @update:model-value="form.annual_price = Number($event) || 0"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="plan-trial">Trial days</Label>
                                <Input
                                    id="plan-trial"
                                    type="number"
                                    placeholder="14"
                                    :model-value="form.trial_days === null ? '' : String(form.trial_days)"
                                    @update:model-value="form.trial_days = num($event)"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="plan-order">Display order</Label>
                                <Input
                                    id="plan-order"
                                    type="number"
                                    :model-value="String(form.display_order)"
                                    @update:model-value="form.display_order = Number($event) || 0"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <div class="space-y-2">
                                <Label for="plan-students">Max students</Label>
                                <Input
                                    id="plan-students"
                                    type="number"
                                    :model-value="form.max_students === null ? '' : String(form.max_students)"
                                    @update:model-value="form.max_students = num($event)"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="plan-staff">Max staff</Label>
                                <Input
                                    id="plan-staff"
                                    type="number"
                                    :model-value="form.max_staff === null ? '' : String(form.max_staff)"
                                    @update:model-value="form.max_staff = num($event)"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="plan-users">Max users</Label>
                                <Input
                                    id="plan-users"
                                    type="number"
                                    :model-value="form.max_users === null ? '' : String(form.max_users)"
                                    @update:model-value="form.max_users = num($event)"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="plan-branches">Max branches</Label>
                                <Input
                                    id="plan-branches"
                                    type="number"
                                    :model-value="form.max_branches === null ? '' : String(form.max_branches)"
                                    @update:model-value="form.max_branches = num($event)"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="plan-storage">Max storage (MB)</Label>
                                <Input
                                    id="plan-storage"
                                    type="number"
                                    :model-value="form.max_storage_mb === null ? '' : String(form.max_storage_mb)"
                                    @update:model-value="form.max_storage_mb = num($event)"
                                />
                            </div>
                            <div class="flex items-center justify-between gap-2 rounded-lg border p-4">
                                <Label for="plan-active" class="font-medium">Active</Label>
                                <Switch id="plan-active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-sm font-medium">Features included</p>
                            <div class="grid max-h-56 grid-cols-2 gap-2 overflow-y-auto rounded-lg border p-4 sm:grid-cols-3">
                                <div v-for="feature in featureCatalog" :key="feature.value" class="flex items-center justify-between gap-2">
                                    <Label class="cursor-pointer text-sm">{{ feature.label }}</Label>
                                    <Switch :checked="form.features[feature.value]" @update:checked="form.features[feature.value] = $event" />
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
                            <Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
