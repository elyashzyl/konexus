<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { Button } from '@/components/ui/button';
import { extractError } from '@/lib/api';
import { useNotificationsStore } from '@/stores/notifications';
import { BellRing, CheckCheck } from 'lucide-vue-next';
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

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }).format(
        new Date(value),
    );
}
</script>

<template>
    <main class="relative min-h-full">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12 lg:pt-16">
            <PortalPageHeader
                :icon="BellRing"
                eyebrow="Records office"
                index="03"
                title="Notification center"
                description="Everything happening around your records office — enrollment moves, announcements and system updates."
            >
                <template #actions>
                    <Button v-if="store.unread > 0" variant="outline" size="sm" @click="store.markAllRead()">
                        <CheckCheck class="size-4" /> Mark all read
                    </Button>
                </template>
            </PortalPageHeader>

            <div class="portal-rise mt-10 flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="filter in FILTERS"
                    :key="filter.key"
                    type="button"
                    class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition"
                    :class="
                        typeFilter === filter.key
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border/70 bg-card text-muted-foreground hover:border-primary/25'
                    "
                    @click="typeFilter = filter.key"
                >
                    {{ filter.label }}
                </button>
                <span class="ml-auto hidden items-center gap-2 text-xs text-muted-foreground sm:flex">
                    {{ store.unread }} unread
                </span>
            </div>

            <div v-if="loading" class="mt-6 space-y-3">
                <div v-for="i in 5" :key="i" class="h-24 animate-pulse rounded-2xl bg-muted/60" />
            </div>

            <PortalEmptyState
                v-else-if="filtered.length === 0"
                class="mt-6"
                :icon="BellRing"
                title="You're all caught up"
                description="No notifications match this view."
            />

            <div v-else class="portal-rise mt-6 space-y-3" style="animation-delay: 80ms">
                <article
                    v-for="item in filtered"
                    :key="item.id"
                    class="relative overflow-hidden rounded-2xl border bg-card p-5 transition-colors"
                    :class="item.read_at ? 'border-border/60' : 'border-primary/40'"
                >
                    <div
                        v-if="!item.read_at"
                        class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/30 to-transparent"
                    />
                    <div class="flex items-start gap-4">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="item.read_at ? 'bg-muted-foreground/25' : 'bg-primary'" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-sm font-medium text-foreground">{{ item.title }}</h3>
                                <span
                                    v-if="item.type"
                                    class="rounded-full border border-border/70 px-2 py-0.5 font-mono text-[10px] uppercase tracking-[0.12em] text-muted-foreground"
                                >
                                    {{ item.type }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm leading-6 text-muted-foreground">{{ item.body }}</p>
                            <p class="mt-2 text-xs text-muted-foreground/70">{{ formatDate(item.created_at) }}</p>
                        </div>
                        <Button
                            v-if="!item.read_at"
                            variant="ghost"
                            size="sm"
                            class="h-7 shrink-0 px-2 text-xs text-muted-foreground"
                            @click="store.markRead(item.id)"
                        >
                            Mark read
                        </Button>
                    </div>
                </article>
            </div>
        </div>
    </main>
</template>