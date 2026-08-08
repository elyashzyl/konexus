<script setup lang="ts">
import { RouterLink } from 'vue-router';

interface Props {
    href: string;
    tabindex?: number;
}

defineProps<Props>();
</script>

<template>
    <RouterLink
        :to="href"
        :tabindex="tabindex"
        class="hover:decoration-current! text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out dark:decoration-neutral-500"
    >
        <slot />
    </RouterLink>
</template>
