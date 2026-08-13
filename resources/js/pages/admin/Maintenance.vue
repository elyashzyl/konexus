<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { extractError } from '@/lib/api';
import { platformApi, triggerDownload, type BackupItem, type SystemHealth } from '@/lib/platformApi';
import { Download, HardDrive, RefreshCw, Server, Trash2, TriangleAlert, Database, Wrench } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const healthLoading = ref(true);
const backupsLoading = ref(true);
const creating = ref(false);
const health = ref<SystemHealth | null>(null);
const backups = ref<BackupItem[]>([]);
const backupNotes = ref('');

onMounted(async () => {
    await Promise.all([loadHealth(), loadBackups()]);
});

async function loadHealth(): Promise<void> {
    healthLoading.value = true;
    try {
        health.value = await platformApi.health();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        healthLoading.value = false;
    }
}

async function loadBackups(): Promise<void> {
    backupsLoading.value = true;
    try {
        const data = await platformApi.backups.index({ page: 1, per_page: 20 });
        backups.value = data.items;
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        backupsLoading.value = false;
    }
}

async function createBackup(): Promise<void> {
    creating.value = true;
    try {
        await platformApi.backups.create({ type: 'manual', notes: backupNotes.value || undefined });
        backupNotes.value = '';
        toast.success('Backup created.');
        await Promise.all([loadBackups(), loadHealth()]);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        creating.value = false;
    }
}

async function download(backup: BackupItem): Promise<void> {
    try {
        const { blob, filename } = await platformApi.backups.download(backup.id);
        triggerDownload(blob, filename);
    } catch (error) {
        toast.error(extractError(error));
    }
}

async function remove(backup: BackupItem): Promise<void> {
    if (!window.confirm(`Delete backup "${backup.file_name}"?`)) return;
    try {
        await platformApi.backups.destroy(backup.id);
        toast.success('Backup deleted.');
        await Promise.all([loadBackups(), loadHealth()]);
    } catch (error) {
        toast.error(extractError(error));
    }
}
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]" />

        <div class="relative w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="Wrench"
                index="06"
                eyebrow="Operations"
                title="Maintenance"
                description="System health, environment and backups."
            />

            <section class="portal-rise mt-10">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="flex items-center gap-2 font-display text-lg font-medium tracking-[-0.01em]">
                            <Server class="size-4 text-primary" /> System health
                        </CardTitle>
                        <Button variant="outline" size="sm" @click="loadHealth"><RefreshCw class="size-4" /> Refresh</Button>
                    </CardHeader>
                    <CardContent>
                        <div v-if="healthLoading" class="space-y-2">
                            <Skeleton v-for="i in 4" :key="i" class="h-8" />
                        </div>
                        <div v-else-if="health" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <p class="text-xs text-muted-foreground">Environment</p>
                                <p class="font-medium">{{ health.app.env }} <template v-if="health.app.debug">· <Badge variant="destructive" class="ml-1 align-middle">DEBUG ON</Badge></template></p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Database</p>
                                <p class="font-medium flex items-center gap-1">
                                    <Database class="size-4 text-muted-foreground" />
                                    {{ health.database.connection }}
                                    <Badge v-if="health.database.connected" variant="outline" class="text-emerald-600">Connected</Badge>
                                    <Badge v-else variant="destructive">Unreachable</Badge>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Cache / Queue</p>
                                <p class="font-medium">{{ health.cache.store }} · {{ health.queue.connection }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Mail driver</p>
                                <p class="font-medium">{{ health.mail.default }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Disk space</p>
                                <p class="font-medium">{{ health.disk_space.free_human ?? '—' }} free</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Pending jobs</p>
                                <p class="font-medium">{{ health.queue.pending_jobs }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section class="portal-rise mt-6" style="animation-delay: 120ms">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 font-display text-lg font-medium tracking-[-0.01em]">
                            <HardDrive class="size-4 text-primary" /> Backups
                        </CardTitle>
                        <CardDescription>
                            Archive of the database and private storage.
                            <template v-if="health?.storage"> Disk: <Badge variant="outline">{{ health.storage.backup_disk }}</Badge></template>
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="flex-1 min-w-56 space-y-2">
                                <p class="text-xs text-muted-foreground">Notes (optional)</p>
                                <Textarea v-model="backupNotes" placeholder="e.g. Before term export" rows="1" />
                            </div>
                            <Button :disabled="creating" @click="createBackup">
                                <TriangleAlert class="size-4" /> {{ creating ? 'Creating…' : 'Create backup' }}
                            </Button>
                        </div>

                        <div v-if="backupsLoading" class="space-y-2">
                            <Skeleton v-for="i in 3" :key="i" class="h-10" />
                        </div>

                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>File</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Size</TableHead>
                                    <TableHead>Created by</TableHead>
                                    <TableHead>When</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="backup in backups" :key="backup.id">
                                    <TableCell>
                                        <div class="font-medium">{{ backup.file_name }}</div>
                                        <div v-if="backup.notes" class="text-xs text-muted-foreground">{{ backup.notes }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{{ backup.type }}</Badge>
                                    </TableCell>
                                    <TableCell>{{ backup.size_human }}</TableCell>
                                    <TableCell>{{ backup.created_by ?? 'System' }}</TableCell>
                                    <TableCell class="text-xs text-muted-foreground">{{ new Date(backup.created_at).toLocaleString() }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button variant="ghost" size="icon" aria-label="Download" @click="download(backup)">
                                                <Download class="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" aria-label="Delete" class="text-destructive" @click="remove(backup)">
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <p v-if="!backupsLoading && backups.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                            No backups yet. Create your first one above.
                        </p>
                    </CardContent>
                </Card>
            </section>
        </div>
    </div>
</template>
