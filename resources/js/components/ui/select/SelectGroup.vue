<script setup lang="ts">
import { SelectGroup, type SelectGroupProps, useForwardProps } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps<SelectGroupProps>();

const delegatedProps = computed(() => props);

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <SelectGroup v-bind="forwarded">
        <slot />
    </SelectGroup>
</template>
