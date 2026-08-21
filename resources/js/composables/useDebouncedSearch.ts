import { onUnmounted, watch, type Ref } from 'vue';

/**
 * Runs `onChange` after `search` stops changing for `delay` ms.
 * Used by list pages whose search input queries the backend.
 */
export function useDebouncedSearch(search: Ref<string>, onChange: () => void, delay = 300): void {
    let timer: ReturnType<typeof setTimeout> | undefined;

    watch(search, () => {
        clearTimeout(timer);
        timer = setTimeout(onChange, delay);
    });

    onUnmounted(() => clearTimeout(timer));
}
