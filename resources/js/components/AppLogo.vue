<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { BRAND } from '@/constants/app';

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex items-center">
        <AppLogoIcon class="size-8 rounded-md" />
        <div class="ml-2 grid flex-1 text-left text-sm">
            <span class="mb-0.5 truncate font-semibold leading-none">{{ BRAND.name }}</span>
        </div>
    </div>
</template>
