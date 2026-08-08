<script setup lang="ts">
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';

const route = useRoute();

const title = computed(() => (route.meta.title as string | undefined) ?? '');
const description = computed(() => (route.meta.description as string | undefined) ?? '');
</script>

<template>
    <AuthSimpleLayout :title="title" :description="description">
        <RouterView />
    </AuthSimpleLayout>
</template>
