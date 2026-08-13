<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { extractError } from '@/lib/api';
import { platformApi, triggerDownload, type ReportContext, type ReportDescriptor } from '@/lib/platformApi';
import { Download, FileBarChart, FileText } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const generating = ref(false);
const reports = ref<ReportDescriptor[]>([]);
const context = ref<ReportContext | null>(null);
const selectedKey = ref('');

onMounted(async () => {
    try {
        const data = await platformApi.reports.catalog();
        reports.value = data.items;
        context.value = data.context;
        selectedKey.value = reports.value[0]?.key ?? '';
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

const grouped = computed(() => {
    const groups: { group: string; reports: ReportDescriptor[] }[] = [];
    const map = new Map<string, ReportDescriptor[]>();
    for (const report of reports.value) {
        if (!map.has(report.group)) map.set(report.group, []);
        map.get(report.group)!.push(report);
    }
    for (const [group, reportsInGroup] of map) {
        groups.push({ group, reports: reportsInGroup });
    }
    return groups;
});

const selectedReport = computed(() => reports.value.find((report) => report.key === selectedKey.value));

async function generate(format: 'csv' | 'pdf'): Promise<void> {
    if (!selectedReport.value) return;

    generating.value = true;
    try {
        const { blob, filename } = await platformApi.reports.download({
            report: selectedReport.value.key,
            format,
            academic_year_id: context.value?.academic_year_id ?? undefined,
            academic_term_id: context.value?.academic_term_id ?? undefined,
        });
        triggerDownload(blob, filename);
        toast.success(`${selectedReport.value.label} exported as ${format.toUpperCase()}.`);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        generating.value = false;
    }
}
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]" />

        <div class="relative w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="FileBarChart"
                index="05"
                eyebrow="Reporting"
                :title="'Reports'"
                :description="context?.academic_year
                    ? `Generate operational reports as CSV or PDF · ${context.academic_year}${context.academic_term ? ` · ${context.academic_term}` : ''}.`
                    : 'Generate operational reports as CSV or PDF.'"
            />

            <div v-if="loading" class="portal-rise mt-10 space-y-2">
                <Skeleton v-for="i in 6" :key="i" class="h-12" />
            </div>

            <template v-else>
                <div class="portal-rise mt-10 grid gap-4 lg:grid-cols-3">
                    <Card class="relative overflow-hidden border-border/60 bg-card/60 lg:col-span-1">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <CardHeader>
                            <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">Available reports</CardTitle>
                            <CardDescription>Pick a report, then choose a format.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div v-for="group in grouped" :key="group.group" class="space-y-1">
                                <p class="text-xs font-medium text-muted-foreground">{{ group.group }}</p>
                                <Button
                                    v-for="report in group.reports"
                                    :key="report.key"
                                    variant="ghost"
                                    size="sm"
                                    class="w-full justify-start"
                                    :class="selectedKey === report.key ? 'bg-muted text-foreground' : ''"
                                    @click="selectedKey = report.key"
                                >
                                    <FileText class="size-4" /> {{ report.label }}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="selectedReport" class="relative overflow-hidden border-border/60 bg-card/60 lg:col-span-2">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">{{ selectedReport.label }}</CardTitle>
                                    <CardDescription>{{ selectedReport.group }}</CardDescription>
                                </div>
                                <Badge variant="secondary">{{ selectedReport.columns.length }} columns</Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-sm text-muted-foreground">Columns included in this report:</p>
                            <div class="flex flex-wrap gap-2">
                                <Badge v-for="column in selectedReport.columns" :key="column" variant="outline">{{ column }}</Badge>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-2">
                                <Button :disabled="generating" @click="generate('csv')">
                                    <Download class="size-4" /> Export CSV
                                </Button>
                                <Button :disabled="generating" variant="outline" @click="generate('pdf')">
                                    <Download class="size-4" /> Export PDF
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </template>
        </div>
    </div>
</template>
