export interface Pagination {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number | null;
    to: number | null;
}

export interface Paginated<T> {
    items: T[];
    pagination: Pagination;
}

export interface CrudQuery {
    page?: number;
    per_page?: number;
    search?: string;
    sort_by?: string;
    sort_dir?: 'asc' | 'desc';
    filter?: Record<string, unknown>;
    is_active?: boolean;
    trashed?: boolean;
}

export type CrudFieldType = 'text' | 'textarea' | 'number' | 'email' | 'url' | 'date' | 'time' | 'select' | 'switch' | 'display';

export interface CrudOption {
    value: string | number;
    label: string;
}

export interface CrudField {
    /** The record attribute this field maps to. */
    name: string;
    label: string;
    type?: CrudFieldType;
    /** Static select options. */
    options?: CrudOption[];
    /** Resource path used to load select options from the API (e.g. 'buildings'). */
    optionsResource?: string;
    /** The record key used as the option label when options are loaded remotely. */
    optionsLabelKey?: string;
    placeholder?: string;
    required?: boolean;
    hint?: string;
    /** Span both columns of the form grid. */
    fullWidth?: boolean;
    /** Read-only display field (not submitted). */
    readOnly?: boolean;
    /** Disable this field when editing an existing record. */
    disabledOnEdit?: boolean;
}

export interface CrudColumn {
    /** The record attribute to read (may be a nested object like `building`). */
    key: string;
    label: string;
    sortable?: boolean;
    align?: 'left' | 'center' | 'right';
    /** Custom cell renderer. Return a primitive; the table formats it. */
    cell?: (row: Record<string, any>) => unknown;
}

export interface CrudItem {
    id: number;
    [key: string]: any;
}
