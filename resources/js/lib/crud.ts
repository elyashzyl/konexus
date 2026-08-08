import api from '@/lib/api';
import type { CrudItem, CrudOption, CrudQuery, Paginated } from '@/types/crud';

/**
 * Thin, type-safe client for the standard CRUD + restore + force-delete
 * endpoints exposed by every Phase 2 module.
 */
export function createResourceApi(resource: string) {
    return {
        index: async (params: CrudQuery): Promise<Paginated<CrudItem>> => {
            const { data } = await api.get<{ data: Paginated<CrudItem> }>(resource, { params });

            return data.data;
        },

        show: async (id: number): Promise<CrudItem> => {
            const { data } = await api.get<{ data: CrudItem }>(`${resource}/${id}`);

            return data.data;
        },

        store: async (payload: Record<string, unknown>): Promise<CrudItem> => {
            const { data } = await api.post<{ data: CrudItem }>(resource, payload);

            return data.data;
        },

        update: async (id: number, payload: Record<string, unknown>): Promise<CrudItem> => {
            const { data } = await api.put<{ data: CrudItem }>(`${resource}/${id}`, payload);

            return data.data;
        },

        destroy: async (id: number): Promise<void> => {
            await api.delete(`${resource}/${id}`);
        },

        restore: async (id: number): Promise<CrudItem> => {
            const { data } = await api.post<{ data: CrudItem }>(`${resource}/${id}/restore`);

            return data.data;
        },

        forceDestroy: async (id: number): Promise<void> => {
            await api.delete(`${resource}/${id}/force`);
        },
    };
}

/**
 * Build select options from a remote resource by fetching its full list.
 */
export async function loadOptions(resource: string, labelKey: string = 'name'): Promise<CrudOption[]> {
    const { data } = await api.get<{ data: Paginated<CrudItem> }>(resource, {
        params: { per_page: 100, sort_by: 'id', sort_dir: 'asc' },
    });

    return data.data.items.map((item) => ({
        value: item.id,
        label: String(item[labelKey] ?? item.name ?? item.id),
    }));
}

export type ResourceApi = ReturnType<typeof createResourceApi>;
