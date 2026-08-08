import { extractError, extractFieldErrors } from '@/lib/api';
import { createResourceApi, type ResourceApi } from '@/lib/crud';
import type { CrudItem, CrudQuery, Pagination } from '@/types/crud';
import { computed, reactive, ref } from 'vue';

export interface CrudStoreOptions {
    resource: string;
    defaultPerPage?: number;
    defaultSortBy?: string;
    defaultSortDir?: 'asc' | 'desc';
}

/**
 * Reactive CRUD state + actions for a single resource endpoint.
 *
 * Returns a plain object of refs and actions (a "store" in spirit); pass the
 * whole object to `<DataTable />` / `<CrudPage />`.
 */
export function useCrudStore(options: CrudStoreOptions) {
    const api: ResourceApi = createResourceApi(options.resource);

    const items = ref<CrudItem[]>([]);
    const pagination = ref<Pagination | null>(null);
    const loading = ref(false);
    const submitting = ref(false);
    const error = ref<string | null>(null);
    const fieldErrors = ref<Record<string, string[]>>({});

    const page = ref(1);
    const perPage = ref(options.defaultPerPage ?? 15);
    const search = ref('');
    const sortBy = ref(options.defaultSortBy ?? 'id');
    const sortDir = ref<'asc' | 'desc'>(options.defaultSortDir ?? 'asc');
    const trashed = ref(false);
    const filters = ref<Record<string, unknown>>({});

    const isEmpty = computed(() => items.value.length === 0);

    function buildQuery(): CrudQuery {
        const query: CrudQuery = {
            page: page.value,
            per_page: perPage.value,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
        };

        if (search.value.trim()) {
            query.search = search.value.trim();
        }

        if (trashed.value) {
            query.trashed = true;
        }

        if (Object.keys(filters.value).length > 0) {
            query.filter = { ...filters.value };
        }

        return query;
    }

    async function fetch(): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const result = await api.index(buildQuery());
            items.value = result.items;
            pagination.value = result.pagination;

            if (pagination.value && pagination.value.last_page > 0 && page.value > pagination.value.last_page) {
                page.value = pagination.value.last_page;
                await fetch();
            }
        } catch (e) {
            error.value = extractError(e);
        } finally {
            loading.value = false;
        }
    }

    async function create(payload: Record<string, unknown>): Promise<CrudItem> {
        submitting.value = true;
        error.value = null;
        fieldErrors.value = {};

        try {
            const item = await api.store(payload);
            await fetch();

            return item;
        } catch (e) {
            fieldErrors.value = extractFieldErrors(e);
            throw e;
        } finally {
            submitting.value = false;
        }
    }

    async function update(id: number, payload: Record<string, unknown>): Promise<CrudItem> {
        submitting.value = true;
        error.value = null;
        fieldErrors.value = {};

        try {
            const item = await api.update(id, payload);
            await fetch();

            return item;
        } catch (e) {
            fieldErrors.value = extractFieldErrors(e);
            throw e;
        } finally {
            submitting.value = false;
        }
    }

    async function remove(id: number): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            await api.destroy(id);
            await fetch();
        } catch (e) {
            error.value = extractError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function restore(id: number): Promise<CrudItem> {
        loading.value = true;
        error.value = null;

        try {
            const item = await api.restore(id);
            await fetch();

            return item;
        } catch (e) {
            error.value = extractError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function forceRemove(id: number): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            await api.forceDestroy(id);
            await fetch();
        } catch (e) {
            error.value = extractError(e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    function reset(): void {
        page.value = 1;
        search.value = '';
        sortBy.value = options.defaultSortBy ?? 'id';
        sortDir.value = options.defaultSortDir ?? 'asc';
        trashed.value = false;
        filters.value = {};
        fetch();
    }

    return reactive({
        api,
        items,
        pagination,
        loading,
        submitting,
        error,
        fieldErrors,
        page,
        perPage,
        search,
        sortBy,
        sortDir,
        trashed,
        filters,
        isEmpty,
        buildQuery,
        fetch,
        create,
        update,
        remove,
        restore,
        forceRemove,
        reset,
    });
}

export type CrudStore = ReturnType<typeof useCrudStore>;
