<script setup lang="ts">
import CrudPage from '@/components/crud/CrudPage.vue';
import { FOUNDATION_MODULES, foundationModuleByKey } from '@/modules/foundation/config';
import { computed } from 'vue';

const props = defineProps<{ moduleKey: string }>();

const module = computed(() => foundationModuleByKey(props.moduleKey)!);

const index = computed(() => String(FOUNDATION_MODULES.findIndex((m) => m.key === props.moduleKey) + 1).padStart(2, '0'));

const eyebrow = computed(() => {
    if (module.value.path.startsWith('/system')) return 'System';
    if (module.value.path.startsWith('/facilities')) return 'Facilities';
    return 'School';
});
</script>

<template>
    <CrudPage
        v-if="module"
        :key="module.key"
        :icon="module.icon"
        :index="index"
        :eyebrow="eyebrow"
        :title="module.title"
        :description="module.description"
        :resource="module.resource"
        :columns="module.columns"
        :fields="module.fields"
        :option-sources="module.optionSources"
        :singular-label="module.singularLabel"
        :searchable="module.searchable"
        :create-route="module.createRoute"
    />
</template>
