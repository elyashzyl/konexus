<script setup lang="ts">
import { SelectValue, type SelectValueProps, useForwardProps } from 'radix-vue';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<SelectValueProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <SelectValue v-bind="forwarded" :class="props.class">
        <slot />
    </SelectValue>
</template>
