<script setup lang="ts">
import CrudPage from '@/components/crud/CrudPage.vue';
import { foundationModuleByKey } from '@/modules/foundation/config';
import { computed } from 'vue';

const module = computed(() => foundationModuleByKey('students')!);
</script>

<template>
    <CrudPage
        v-if="module"
        :key="module.key"
        :icon="module.icon"
        :eyebrow="'Records office'"
        :title="module.title"
        :description="module.description"
        :resource="module.resource"
        :columns="module.columns"
        :fields="module.fields"
        :option-sources="module.optionSources"
        :singular-label="module.singularLabel"
        :searchable="module.searchable"
        read-only
    />
</template>