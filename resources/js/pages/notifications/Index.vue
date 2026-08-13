<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Badge } from '@/components/ui/badge';
import { extractError } from '@/lib/api';
import { useNotificationsStore } from '@/stores/notifications';
import { BellRing, CheckCheck, Filter } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const store = useNotificationsStore();
const loading = ref(true);
const typeFilter = ref<'all' | 'announcement' | 'grade' | 'enrollment' | 'system'>('all');

const FILTERS: { key: typeof typeFilter.value; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'announcement', label: 'Announcements' },
    { key: 'grade', label: 'Grades' },
    { key: 'enrollment', label: 'Enrollments' },
    { key: 'system', label: 'System' },
];

onMounted(async () => {
    try {
        await Promise.all([store.load(1, 30), store.loadPreferences()]);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

const filtered = computed(() => {
    if (typeFilter.value === 'all') return store.items;
    return store.items.filter((item) => item.type === typeFilter.value);
});
</script>

<template>
    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Notification Center</h1>
                <p class="text-sm text-muted-foreground">Everything happening around you.</p>
            </div>
            <Button v-if="store.unread > 0" variant="outline" size="sm" @click="store.markAllRead()">
                <CheckCheck class="size-4" /> Mark all read
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button
                v-for="filter in FILTERS"
                :key="filter.key"
                variant="ghost"
                size="sm"
                :class="typeFilter === filter.key ? 'bg-muted text-foreground' : ''"
                @click="typeFilter = filter.key"
            >
                {{ filter.label }}
            </Button>
            <div class="ml-auto flex items-center gap-2 text-xs text-muted-foreground">
                <Filter class="size-3.5" />
                <span>{{ store.unread }} unread</span>
            </div>
        </div>

        <div v-if="loading" class="space-y-2">
            <Skeleton v-for="i in 5" :key="i" class="h-16" />
        </div>

        <div v-else-if="filtered.length === 0" class="grid place-items-center rounded-xl border py-20">
            <div class="flex max-w-sm flex-col items-center gap-3 text-center">
                <BellRing class="size-10 text-muted-foreground" />
                <h2 class="text-lg font-semibold">You're all caught up</h2>
                <p class="text-sm text-muted-foreground">No notifications match this view.</p>
            </div>
        </div>

        <div v-else class="space-y-2">
            <Card
                v-for="item in filtered"
                :key="item.id"
                :class="!item.read_at ? 'border-primary/50' : ''"
                class="cursor-pointer transition-colors hover:bg-muted/50"
                @click="store.markRead(item.id)"
            >
                <CardHeader class="flex flex-row items-start gap-3 space-y-0 p-4">
                    <div class="flex flex-col items-center gap-1 py-1">
                        <span class="size-2 rounded-full" :class="item.read_at ? 'bg-transparent' : 'bg-primary'" />
                    </div>
                    <div class="flex-1">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            {{ item.title }}
                            <Badge v-if="item.type" variant="outline" class="text-[10px]">{{ item.type }}</Badge>
                        </CardTitle>
                        <p class="mt-1 text-sm text-muted-foreground">{{ item.body }}</p>
                        <p class="mt-1 text-xs text-muted-foreground/70">{{ new Date(item.created_at).toLocaleString() }}</p>
                    </div>
                </CardHeader>
            </Card>
        </div>
    </div>
</template>
