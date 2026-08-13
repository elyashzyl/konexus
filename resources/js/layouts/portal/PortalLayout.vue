<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import PortalSidebar from '@/components/portal/PortalSidebar.vue';
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';

export type PortalRole = 'student' | 'parent' | 'teacher' | 'staff';

const route = useRoute();

const portalRole = computed<PortalRole>(() => (route.meta.portalRole as PortalRole | undefined) ?? 'student');
const breadcrumbs = computed<BreadcrumbItemType[]>(() => (route.meta.breadcrumbs as BreadcrumbItemType[] | undefined) ?? []);
</script>

<template>
    <AppShell variant="sidebar">
        <PortalSidebar :role="portalRole" />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <RouterView />
        </AppContent>
    </AppShell>
</template>
