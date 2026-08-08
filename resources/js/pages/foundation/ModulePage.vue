<script setup lang="ts">
import CrudPage from '@/components/crud/CrudPage.vue';
import { foundationModuleByKey } from '@/modules/foundation/config';

const props = defineProps<{ moduleKey: string }>();

const module = foundationModuleByKey(props.moduleKey)!;
</script>

<template>
    <CrudPage
        v-if="module"
        :title="module.title"
        :description="module.description"
        :resource="module.resource"
        :columns="module.columns"
        :fields="module.fields"
        :option-sources="module.optionSources"
        :singular-label="module.singularLabel"
        :searchable="module.searchable"
    />
</template>
