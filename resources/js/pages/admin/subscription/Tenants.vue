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
import { subscriptionApi, type TenantItem, type TenantUsage } from '@/lib/subscriptionApi';
import { Building2, ChartNoAxesCombined, Pause, Pencil, Play, Trash2 } from 'lucide-vue-next';
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
const form = ref<{ name: string; code: string }>({ name: '', code: '' });

const actionDialogOpen = ref(false);
const actionKind = ref<'suspend' | 'resume'>('suspend');
const actionTarget = ref<TenantItem | null>(null);
const actionReason = ref('');

const usageOpen = ref(false);
const usageTarget = ref<TenantItem | null>(null);
const usageDetail = ref<TenantUsage | null>(null);
const usageLoading = ref(false);
const usageCapturing = ref(false);

async function openUsage(tenant: TenantItem): Promise<void> {
    usageTarget.value = tenant;
    usageDetail.value = null;
    usageOpen.value = true;
    usageLoading.value = true;
    try {
        usageDetail.value = await subscriptionApi.usage.tenant(tenant.id);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        usageLoading.value = false;
    }
}

async function captureUsageSnapshot(): Promise<void> {
    if (!usageTarget.value) return;
    usageCapturing.value = true;
    try {
        await subscriptionApi.usage.snapshot(usageTarget.value.id);
        toast.success('Snapshot captured.');
        usageDetail.value = await subscriptionApi.usage.tenant(usageTarget.value.id);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        usageCapturing.value = false;
    }
}

const usagePct = (used: number, limit: number | null | undefined) => (limit ? Math.min(100, Math.round((used / limit) * 100)) : 0);
const usageBarTone = (used: number, limit: number | null | undefined) => {
    const p = usagePct(used, limit);
    if (p >= 100) return 'bg-destructive';
    if (p >= 80) return 'bg-orange-500';
    return 'bg-primary';
};

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

function openEdit(tenant: TenantItem): void {
    editing.value = tenant;
    form.value = { name: tenant.name, code: tenant.code };
    fieldErrors.value = {};
    dialogOpen.value = true;
}

async function save(): Promise<void> {
    saving.value = true;
    fieldErrors.value = {};
    try {
        const updated = await subscriptionApi.tenants.update(editing.value!.id, {
            name: form.value.name,
            ...(form.value.code ? { code: form.value.code } : {}),
        });
        toast.success(`Tenant "${updated.name}" updated.`);
        dialogOpen.value = false;
        await refresh();
    } catch (error) {
        fieldErrors.value = extractFieldErrors(error);
        toast.error(extractError(error));
    } finally {
        saving.value = false;
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

const lastPage = computed(() => Math.max(1, Math.ceil(total.value / perPage)));
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
                            <SelectItem value="onboarding">Onboarding</SelectItem>
                            <SelectItem value="deactivated">Deactivated</SelectItem>
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
                                    <TableCell
                                        ><Badge variant="secondary">{{ tenant.status }}</Badge></TableCell
                                    >
                                    <TableCell class="text-sm">{{ tenant.subscription_count ?? 0 }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button variant="ghost" size="icon" aria-label="Usage" @click="openUsage(tenant)">
                                                <ChartNoAxesCombined class="size-4" />
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
                                            <Button variant="ghost" size="icon" aria-label="Edit" @click="openEdit(tenant)">
                                                <Pencil class="size-4" />
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

            <Dialog v-model:open="dialogOpen">
                <DialogContent class="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle v-if="editing">Edit {{ editing.name }}</DialogTitle>
                        <DialogDescription>Adjust the display name or code of this registered school.</DialogDescription>
                    </DialogHeader>
                    <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="save">
                        <div class="space-y-2">
                            <Label for="tenant-name">Name</Label>
                            <Input id="tenant-name" v-model="form.name" placeholder="Maple Grove Academy" />
                            <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name[0] }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="tenant-code">Code (optional)</Label>
                            <Input id="tenant-code" v-model="form.code" placeholder="MG-ACAD" />
                            <p v-if="fieldErrors.code" class="text-xs text-destructive">{{ fieldErrors.code[0] }}</p>
                        </div>
                        <DialogFooter class="col-span-full">
                            <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
                            <Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</Button>
                        </DialogFooter>
                    </form>
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

            <Dialog v-model:open="usageOpen">
                <DialogContent class="sm:max-w-3xl">
                    <DialogHeader class="flex-row items-start justify-between">
                        <div>
                            <DialogTitle>Usage</DialogTitle>
                            <DialogDescription>{{ usageTarget?.name }} · resource consumption and limit health</DialogDescription>
                        </div>
                        <Button variant="outline" size="sm" :disabled="usageCapturing" @click="captureUsageSnapshot">
                            {{ usageCapturing ? 'Capturing…' : 'Capture snapshot' }}
                        </Button>
                    </DialogHeader>
                    <div v-if="usageLoading" class="space-y-2">
                        <Skeleton v-for="i in 4" :key="i" class="h-24" />
                    </div>
                    <template v-else-if="usageDetail">
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card class="border-border/60 bg-card/60">
                                <CardHeader><CardTitle class="font-display text-sm font-medium">Students</CardTitle></CardHeader>
                                <CardContent>
                                    <p class="font-display text-2xl font-medium">{{ usageDetail.snapshot.students_count }}</p>
                                    <p class="text-xs text-muted-foreground">Limit {{ usageDetail.limit_status.limits.max_students ?? '∞' }}</p>
                                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-muted">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="usageBarTone(usageDetail.snapshot.students_count, usageDetail.limit_status.limits.max_students)"
                                            :style="{ width: `${usagePct(usageDetail.snapshot.students_count, usageDetail.limit_status.limits.max_students)}%` }"
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                            <Card class="border-border/60 bg-card/60">
                                <CardHeader><CardTitle class="font-display text-sm font-medium">Users</CardTitle></CardHeader>
                                <CardContent>
                                    <p class="font-display text-2xl font-medium">{{ usageDetail.snapshot.users_count }}</p>
                                    <p class="text-xs text-muted-foreground">Limit {{ usageDetail.limit_status.limits.max_users ?? '∞' }}</p>
                                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-muted">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="usageBarTone(usageDetail.snapshot.users_count, usageDetail.limit_status.limits.max_users)"
                                            :style="{ width: `${usagePct(usageDetail.snapshot.users_count, usageDetail.limit_status.limits.max_users)}%` }"
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                            <Card class="border-border/60 bg-card/60">
                                <CardHeader><CardTitle class="font-display text-sm font-medium">Branches</CardTitle></CardHeader>
                                <CardContent>
                                    <p class="font-display text-2xl font-medium">{{ usageDetail.snapshot.branches_count }}</p>
                                    <p class="text-xs text-muted-foreground">Limit {{ usageDetail.limit_status.limits.max_branches ?? '∞' }}</p>
                                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-muted">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="usageBarTone(usageDetail.snapshot.branches_count, usageDetail.limit_status.limits.max_branches)"
                                            :style="{ width: `${usagePct(usageDetail.snapshot.branches_count, usageDetail.limit_status.limits.max_branches)}%` }"
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                            <Card class="border-border/60 bg-card/60">
                                <CardHeader><CardTitle class="font-display text-sm font-medium">Storage</CardTitle></CardHeader>
                                <CardContent>
                                    <p class="font-display text-2xl font-medium">{{ usageDetail.snapshot.storage_mb }} MB</p>
                                    <p class="text-xs text-muted-foreground">Limit {{ usageDetail.limit_status.limits.max_storage_mb ?? '∞' }} MB</p>
                                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-muted">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="usageBarTone(usageDetail.snapshot.storage_mb, usageDetail.limit_status.limits.max_storage_mb)"
                                            :style="{ width: `${usagePct(usageDetail.snapshot.storage_mb, usageDetail.limit_status.limits.max_storage_mb)}%` }"
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <div v-if="usageDetail.limit_status.warnings.length" class="space-y-2">
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

                        <div v-if="usageDetail.trend.length">
                            <p class="mb-2 text-sm font-medium">Snapshot history</p>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Period</TableHead>
                                        <TableHead>Students</TableHead>
                                        <TableHead>Users</TableHead>
                                        <TableHead>Branches</TableHead>
                                        <TableHead>Storage</TableHead>
                                        <TableHead>Captured</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="snapshot in usageDetail.trend" :key="snapshot.id">
                                        <TableCell class="text-sm">{{ snapshot.period_month }}/{{ snapshot.period_year }}</TableCell>
                                        <TableCell>{{ snapshot.students_count }}</TableCell>
                                        <TableCell>{{ snapshot.users_count }}</TableCell>
                                        <TableCell>{{ snapshot.branches_count }}</TableCell>
                                        <TableCell>{{ snapshot.storage_mb }} MB</TableCell>
                                        <TableCell class="text-xs text-muted-foreground">{{ snapshot.captured_at ?? '—' }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </template>
                    <p v-else class="py-6 text-center text-sm text-muted-foreground">No usage snapshot available.</p>
                    <DialogFooter>
                        <Button variant="outline" @click="usageOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
