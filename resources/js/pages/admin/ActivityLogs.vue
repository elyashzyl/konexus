<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { extractError } from '@/lib/api';
import { platformApi } from '@/lib/platformApi';
import { subscriptionApi, type AuditActionOption, type AuditEntry } from '@/lib/subscriptionApi';
import type { ActivityLogEntry } from '@/types/platform';
import { Activity, Filter, RefreshCw } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue-sonner';

const route = useRoute();

const source = ref<'system' | 'subscription'>('system');

const loading = ref(true);
const items = ref<ActivityLogEntry[]>([]);
const subItems = ref<AuditEntry[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = 20;
const logNameFilter = ref<'all' | string>('all');
const search = ref('');
const logNames = ref<string[]>([]);

const subActions = ref<AuditActionOption[]>([]);
const subActionFilter = ref('all');

const subDetailOpen = ref(false);
const subDetail = ref<AuditEntry | null>(null);

onMounted(async () => {
    if (route.query.source === 'subscription') {
        source.value = 'subscription';
    }
    try {
        const [actions] = await Promise.all([
            subscriptionApi.audit.actions().catch(() => [] as AuditActionOption[]),
            refresh(),
        ]);
        subActions.value = actions;
    } catch {
        // ignore
    }
});

async function refresh(): Promise<void> {
    loading.value = true;
    try {
        if (source.value === 'subscription') {
            const data = await subscriptionApi.audit.index({
                page: page.value,
                per_page: perPage,
                action: subActionFilter.value === 'all' ? undefined : subActionFilter.value,
            });
            subItems.value = data.items;
            total.value = data.pagination.total;
        } else {
            const stats = await platformApi.activityLogs.stats();
            logNames.value = stats.log_names.map((item) => item.log_name);

            const data = await platformApi.activityLogs.index({
                page: page.value,
                per_page: perPage,
                log_name: logNameFilter.value === 'all' ? undefined : logNameFilter.value,
                search: search.value || undefined,
            });
            items.value = data.items;
            total.value = data.pagination.total;
        }
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
}

function applyFilters(): void {
    page.value = 1;
    refresh();
}

function switchSource(next: 'system' | 'subscription'): void {
    if (source.value === next) return;
    source.value = next;
    page.value = 1;
    refresh();
}

async function openSubDetail(entry: AuditEntry): Promise<void> {
    subDetail.value = null;
    subDetailOpen.value = true;
    try {
        subDetail.value = await subscriptionApi.audit.show(entry.id);
    } catch (error) {
        toast.error(extractError(error));
    }
}

const lastPage = () => Math.max(1, Math.ceil(total.value / perPage));
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]" />

        <div class="relative w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="Activity"
                index="03"
                eyebrow="Audit trail"
                title="Audit & Activity"
                description="The full audit trail of the platform."
            >
                <template #actions>
                    <Button variant="outline" size="sm" @click="refresh">
                        <RefreshCw class="size-4" /> Refresh
                    </Button>
                </template>
            </AdminPageHeader>

            <div class="portal-rise mt-8 inline-flex rounded-lg border border-border/60 bg-muted/40 p-1">
                <Button
                    variant="ghost"
                    size="sm"
                    :class="source === 'system' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground'"
                    @click="switchSource('system')"
                >
                    System activity
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    :class="source === 'subscription' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground'"
                    @click="switchSource('subscription')"
                >
                    Subscription audit
                </Button>
            </div>

            <section class="portal-rise mt-8">
                <div class="flex flex-wrap items-center gap-2">
                    <template v-if="source === 'system'">
                        <div class="flex items-center gap-2">
                            <Filter class="size-4 text-muted-foreground" />
                            <Select :model-value="logNameFilter" @update:model-value="(v: string) => { logNameFilter = v; applyFilters(); }">
                                <SelectTrigger class="w-56">
                                    <SelectValue :placeholder="logNameFilter === 'all' ? 'All modules' : logNameFilter" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All modules</SelectItem>
                                    <SelectItem v-for="name in logNames" :key="name" :value="name">{{ name }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input v-model="search" placeholder="Search activity…" class="w-56" @keydown.enter="applyFilters" />
                            <Button variant="ghost" size="sm" @click="applyFilters">Apply</Button>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex items-center gap-2">
                            <Filter class="size-4 text-muted-foreground" />
                            <Select :model-value="subActionFilter" @update:model-value="(v: string) => { subActionFilter = v; applyFilters(); }">
                                <SelectTrigger class="w-56"><SelectValue placeholder="All actions" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All actions</SelectItem>
                                    <SelectItem v-for="action in subActions" :key="action.value" :value="action.value">{{ action.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </template>
                    <div class="ml-auto text-sm text-muted-foreground">{{ total }} entries</div>
                </div>
            </section>

            <section class="portal-rise mt-6" style="animation-delay: 120ms">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader>
                        <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">Activity timeline</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="loading" class="space-y-2">
                            <Skeleton v-for="i in 6" :key="i" class="h-12" />
                        </div>

                        <Table v-else-if="source === 'system'">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Module</TableHead>
                                    <TableHead>Causer</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="entry in items" :key="entry.id">
                                    <TableCell class="whitespace-nowrap text-xs text-muted-foreground">
                                        {{ new Date(entry.created_at).toLocaleString() }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="font-medium">{{ entry.description }}</div>
                                        <div v-if="entry.event" class="text-xs text-muted-foreground">{{ entry.event }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{{ entry.log_name }}</Badge>
                                    </TableCell>
                                    <TableCell>{{ entry.causer_name ?? 'System' }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Action</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Tenant</TableHead>
                                    <TableHead>When</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="entry in subItems" :key="entry.id" class="cursor-pointer" @click="openSubDetail(entry)">
                                    <TableCell><Badge variant="secondary">{{ entry.action }}</Badge></TableCell>
                                    <TableCell class="max-w-md truncate text-sm">{{ entry.description }}</TableCell>
                                    <TableCell class="text-sm">{{ entry.tenant?.name ?? 'Platform' }}</TableCell>
                                    <TableCell class="text-xs text-muted-foreground">{{ new Date(entry.created_at).toLocaleString() }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div v-if="!loading && (source === 'system' ? items.length === 0 : subItems.length === 0)" class="py-10 text-center text-sm text-muted-foreground">
                            No activity matches these filters.
                        </div>

                        <div v-if="total > perPage" class="mt-4 flex items-center justify-end gap-2">
                            <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--; refresh()">Previous</Button>
                            <span class="text-sm text-muted-foreground">Page {{ page }} of {{ lastPage() }}</span>
                            <Button variant="outline" size="sm" :disabled="page >= lastPage()" @click="page++; refresh()">Next</Button>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </div>
    </div>

    <Dialog v-model:open="subDetailOpen">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{ subDetail?.action }}</DialogTitle>
                <DialogDescription>{{ subDetail?.created_at ? new Date(subDetail.created_at).toLocaleString() : '' }}</DialogDescription>
            </DialogHeader>
            <div v-if="subDetail" class="space-y-4">
                <div>
                    <p class="mb-1 text-sm font-medium">Description</p>
                    <p class="text-sm text-muted-foreground">{{ subDetail.description }}</p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Tenant</span><span>{{ subDetail.tenant?.name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Actor</span><span>{{ subDetail.actor_id ?? 'System' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">IP</span><span>{{ subDetail.ip_address ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Reason</span><span>{{ subDetail.reason ?? '—' }}</span>
                    </div>
                </div>
                <div v-if="subDetail.old_value || subDetail.new_value" class="grid grid-cols-2 gap-3">
                    <div v-if="subDetail.old_value" class="rounded-lg border bg-muted/40 p-3">
                        <p class="mb-2 text-xs font-medium uppercase text-muted-foreground">Before</p>
                        <pre class="max-h-48 overflow-auto whitespace-pre-wrap font-mono text-xs">{{
                            JSON.stringify(subDetail.old_value, null, 2)
                        }}</pre>
                    </div>
                    <div v-if="subDetail.new_value" class="rounded-lg border bg-muted/40 p-3">
                        <p class="mb-2 text-xs font-medium uppercase text-muted-foreground">After</p>
                        <pre class="max-h-48 overflow-auto whitespace-pre-wrap font-mono text-xs">{{
                            JSON.stringify(subDetail.new_value, null, 2)
                        }}</pre>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
