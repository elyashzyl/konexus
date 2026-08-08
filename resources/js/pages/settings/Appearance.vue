<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Appearance settings" description="Update your account's appearance settings" />
        <AppearanceTabs />
    </div>
</template>
