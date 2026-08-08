import type { LucideIcon } from 'lucide-vue-next';

export interface Role {
    id: number;
    name: string;
    label: string;
    description: string | null;
    guard_name: string;
    created_at: string | null;
    updated_at: string | null;
}

export interface RoleCatalogEntry {
    key: string;
    label: string;
    description: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    email_verified_at: string | null;
    is_active: boolean;
    last_login_at: string | null;
    roles: Role[];
    created_at: string;
    updated_at: string;
}

export interface AuthPayload {
    token: string;
    token_type: string;
    expires_in: number;
    user: User;
}

export interface Session {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    created_at: string;
    expires_at: string | null;
    is_current: boolean;
}

export interface ApiResponse<T = unknown> {
    success: boolean;
    message: string;
    data: T;
    errors: Record<string, string[]> | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface RouteMetaType {
    title?: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
    requiresAuth?: boolean;
    requiresGuest?: boolean;
}
