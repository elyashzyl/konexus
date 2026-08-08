<script setup lang="ts">
import { RouterView } from 'vue-router';
import { Toaster } from 'vue-sonner';
</script>

<template>
    <RouterView />

    <Toaster richColors position="top-right" :toast-options="{ class: 'rounded-xl' }" />
</template>
