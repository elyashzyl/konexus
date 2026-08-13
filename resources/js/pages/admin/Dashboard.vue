<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { extractError } from '@/lib/api';
import { platformApi, type AdminDashboardSnapshot } from '@/lib/platformApi';
import { Activity, BarChart3, BookOpen, CalendarRange, GraduationCap, Users } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const snapshot = ref<AdminDashboardSnapshot | null>(null);

onMounted(async () => {
    try {
        snapshot.value = await platformApi.admin.dashboard();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

const highlightCards = computed(() => {
    const counters = snapshot.value?.counters ?? {};
    return [
        { label: 'Students', value: counters.students ?? 0, icon: GraduationCap },
        { label: 'Active enrollments', value: counters.active_enrollments ?? 0, icon: CalendarRange },
        { label: 'Teachers', value: counters.teachers ?? 0, icon: Users },
        { label: 'Grade records', value: counters.grade_records ?? 0, icon: BookOpen },
    ];
});

const maxTrend = computed(() => {
    const trend = snapshot.value?.enrollment_trend ?? [];
    return Math.max(1, ...trend.map((item) => Math.max(item.enrollments, item.users)));
});
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]" />

        <div class="relative w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="BarChart3"
                index="01"
                eyebrow="Operational analytics"
                :title="'Admin Dashboard'"
                :description="snapshot?.context.academic_year
                    ? `Live overview for ${snapshot.context.academic_year.name}${snapshot.context.academic_term ? ` · ${snapshot.context.academic_term.name}` : ''}.`
                    : 'Live overview of enrollments, grades and recent activity.'"
            />

            <div v-if="loading" class="portal-rise mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Skeleton v-for="i in 4" :key="i" class="h-32 rounded-2xl" />
            </div>

            <div v-else>
                <section class="portal-rise mt-12">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="(card, index) in highlightCards"
                            :key="card.label"
                            class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/60 p-6"
                        >
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                            <div class="flex items-center justify-between">
                                <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                                <component :is="card.icon" class="size-4 text-primary" />
                            </div>
                            <p class="mt-5 text-[13px] font-medium text-muted-foreground">{{ card.label }}</p>
                            <p class="mt-1 font-display text-4xl font-medium tracking-[-0.02em] text-foreground">{{ card.value }}</p>
                        </div>
                    </div>
                </section>

                <section class="portal-rise mt-10" style="animation-delay: 120ms">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <Card class="relative overflow-hidden border-border/60 bg-card/60">
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2 font-display text-lg font-medium tracking-[-0.01em]">
                                    <BarChart3 class="size-4 text-primary" /> Enrollment trend
                                </CardTitle>
                                <CardDescription>New enrollments vs new user accounts (6 months).</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="flex h-48 items-end gap-3">
                                    <div v-for="item in snapshot?.enrollment_trend ?? []" :key="item.month" class="flex flex-1 flex-col items-center gap-2">
                                        <div class="flex w-full flex-1 items-end justify-center gap-1">
                                            <div
                                                class="w-3 rounded-t bg-primary/80"
                                                :style="{ height: `${(item.enrollments / maxTrend) * 100}%` }"
                                                :title="`Enrollments: ${item.enrollments}`"
                                            />
                                            <div
                                                class="w-3 rounded-t bg-muted-foreground/40"
                                                :style="{ height: `${(item.users / maxTrend) * 100}%` }"
                                                :title="`Users: ${item.users}`"
                                            />
                                        </div>
                                        <span class="text-xs text-muted-foreground">{{ item.label }}</span>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center gap-4 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-primary/80" /> Enrollments</span>
                                    <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-muted-foreground/40" /> Users</span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="relative overflow-hidden border-border/60 bg-card/60">
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2 font-display text-lg font-medium tracking-[-0.01em]">
                                    <BarChart3 class="size-4 text-primary" /> Enrollment by status
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-2">
                                    <p v-if="(snapshot?.enrollment_status ?? []).length === 0" class="text-sm text-muted-foreground">No enrollment data.</p>
                                    <div v-for="item in snapshot?.enrollment_status ?? []" :key="item.status" class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-2.5 text-sm">
                                        <span>{{ item.label }}</span>
                                        <Badge variant="outline">{{ item.total }}</Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <section class="portal-rise mt-6" style="animation-delay: 180ms">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <Card class="relative overflow-hidden border-border/60 bg-card/60">
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2 font-display text-lg font-medium tracking-[-0.01em]">
                                    <BarChart3 class="size-4 text-primary" /> Grade records by status
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-2">
                                    <p v-if="(snapshot?.grade_status ?? []).length === 0" class="text-sm text-muted-foreground">No grade records yet.</p>
                                    <div v-for="item in snapshot?.grade_status ?? []" :key="item.status" class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-2.5 text-sm">
                                        <span class="capitalize">{{ item.status }}</span>
                                        <Badge variant="outline">{{ item.total }}</Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="relative overflow-hidden border-border/60 bg-card/60">
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2 font-display text-lg font-medium tracking-[-0.01em]">
                                    <Activity class="size-4 text-primary" /> Recent activity
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Event</TableHead>
                                            <TableHead>Causer</TableHead>
                                            <TableHead class="text-right">When</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="entry in snapshot?.activity ?? []" :key="entry.id">
                                            <TableCell>
                                                <div class="text-sm font-medium">{{ entry.description }}</div>
                                                <div class="text-xs text-muted-foreground">{{ entry.log_name }}</div>
                                            </TableCell>
                                            <TableCell class="text-sm">{{ entry.causer_name ?? 'System' }}</TableCell>
                                            <TableCell class="text-right text-xs text-muted-foreground">{{ new Date(entry.created_at).toLocaleDateString() }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                                <p v-if="(snapshot?.activity ?? []).length === 0" class="py-4 text-center text-sm text-muted-foreground">No recent activity.</p>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>