<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { extractError } from '@/lib/api';
import { platformApi } from '@/lib/platformApi';
import type { AnnouncementItem } from '@/types/platform';
import { Megaphone } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const announcements = ref<AnnouncementItem[]>([]);

onMounted(async () => {
    try {
        announcements.value = await platformApi.announcements.mine();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

function formatDate(value?: string | null): string {
    if (!value) return '—';
    return new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
}

function pad(index: number): string {
    return String(index + 1).padStart(2, '0');
}
</script>

<template>
    <main class="w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12 lg:pt-16">
        <PortalPageHeader
            :icon="Megaphone"
            eyebrow="School life"
            index="02"
            title="Announcements"
            description="School news and official notices addressed to your office."
        />

        <div v-if="loading" class="mt-12 space-y-3">
            <div v-for="i in 4" :key="i" class="h-12 animate-pulse rounded-xl bg-muted/60" />
        </div>

        <div v-else-if="!announcements.length" class="mt-12">
            <PortalEmptyState
                :icon="Megaphone"
                index="02"
                title="Nothing announced yet"
                description="New notices and announcements will appear here."
            />
        </div>

        <div v-else class="portal-rise mt-12 divide-y divide-border/60 border-y border-border/60">
            <article v-for="(announcement, index) in announcements" :key="announcement.id" class="flex items-start gap-4 py-6">
                <span class="index-num w-7 shrink-0 pt-1 font-mono text-xs text-muted-foreground/60">{{ pad(index) }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-[15px] font-medium text-foreground">{{ announcement.title }}</p>
                    <p class="mt-1.5 text-sm leading-6 text-muted-foreground">{{ announcement.content }}</p>
                    <p class="mt-3 text-xs text-muted-foreground/70">
                        {{ formatDate(announcement.published_at) }}
                        <span v-if="announcement.author"> · {{ announcement.author.name }}</span>
                    </p>
                </div>
                <span v-if="announcement.category" class="shrink-0 rounded-full border border-primary/15 bg-primary/6 px-3 py-1 font-mono text-[11px] text-primary">
                    {{ announcement.category }}
                </span>
            </article>
        </div>
    </main>
</template>
