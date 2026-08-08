<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';

const route = useRoute();

const breadcrumbs = computed<BreadcrumbItemType[]>(() => (route.meta.breadcrumbs as BreadcrumbItemType[] | undefined) ?? []);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <RouterView />
    </AppLayout>
</template>
