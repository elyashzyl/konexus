<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { extractError } from '@/lib/api';
import { portalApi } from '@/lib/portalApi';
import type { PortalDocument } from '@/types/platform';
import { ArrowLeft, ArrowRight, FileText } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue-sonner';

const route = useRoute();
const childId = computed(() => Number(route.params.id));

const loading = ref(true);
const documents = ref<PortalDocument[]>([]);

onMounted(async () => {
    try {
        documents.value = await portalApi.parent.documents(childId.value);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

function pad(index: number): string {
    return String(index + 1).padStart(2, '0');
}
</script>

<template>
    <main class="w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12 lg:pt-16">
        <RouterLink
            :to="`/portal/parent/children/${childId}`"
            class="portal-rise inline-flex items-center gap-2 px-2 text-sm font-medium text-primary hover:underline"
        >
            <ArrowLeft class="size-4" />
            Child overview
        </RouterLink>

        <div class="mt-8">
            <PortalPageHeader
                :icon="FileText"
                eyebrow="Secure file vault"
                index="04"
                title="Private documents"
                description="Secure files released by the registrar, kept for your records."
            />
        </div>

        <div v-if="loading" class="mt-12 space-y-3">
            <div v-for="i in 4" :key="i" class="h-12 animate-pulse rounded-xl bg-muted/60" />
        </div>

        <div v-else-if="!documents.length" class="mt-12">
            <PortalEmptyState
                :icon="FileText"
                index="04"
                title="No documents yet"
                description="Files released by the registrar will appear here."
            />
        </div>

        <div v-else class="portal-rise mt-12 divide-y divide-border/60 border-y border-border/60">
            <a
                v-for="(document, index) in documents"
                :key="document.id"
                :href="document.url || undefined"
                target="_blank"
                rel="noopener"
                class="group flex items-center gap-4 py-4"
            >
                <span class="index-num w-7 shrink-0 font-mono text-xs text-muted-foreground/60">{{ pad(index) }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[15px] font-medium text-foreground group-hover:underline">{{ document.name }}</span>
                    <span class="mt-0.5 block text-xs text-muted-foreground">{{ document.document_type }}</span>
                </span>
                <ArrowRight class="size-4 shrink-0 text-muted-foreground/40 transition group-hover:translate-x-0.5 group-hover:text-primary" />
            </a>
        </div>
    </main>
</template>
