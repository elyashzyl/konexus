<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { extractError } from '@/lib/api';
import { platformApi } from '@/lib/platformApi';
import type { ActivityLogEntry } from '@/types/platform';
import { Activity, Filter, RefreshCw } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const items = ref<ActivityLogEntry[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = 20;
const logNameFilter = ref<'all' | string>('all');
const search = ref('');
const logNames = ref<string[]>([]);

onMounted(async () => {
    await refresh();
});

async function refresh(): Promise<void> {
    loading.value = true;
    try {
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

            <section class="portal-rise mt-10">
                <div class="flex flex-wrap items-center gap-2">
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
                        <Input
                            v-model="search"
                            placeholder="Search activity…"
                            class="w-56"
                            @keydown.enter="applyFilters"
                        />
                        <Button variant="ghost" size="sm" @click="applyFilters">Apply</Button>
                    </div>
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

                        <Table v-else>
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

                        <div v-if="!loading && items.length === 0" class="py-10 text-center text-sm text-muted-foreground">
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
</template>
