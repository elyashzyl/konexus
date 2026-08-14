import api from '@/lib/api';
import type { Paginated } from '@/types/crud';
import type { ActivityLogEntry, AppNotification, NotificationPreferenceMatrix } from '@/types/platform';

export const platformApi = {
    notifications: {
        index: (params: { page?: number; per_page?: number; unread_only?: boolean; type?: string }) =>
            api.get<{ data: Paginated<AppNotification> }>('/notifications', { params }).then((r) => r.data.data),
        unreadCount: () => api.get<{ data: { unread: number } }>('/notifications/unread-count').then((r) => r.data.data),
        markRead: (id: string) => api.patch<{ data: AppNotification }>(`/notifications/${id}/read`).then((r) => r.data.data),
        markAllRead: () => api.patch<{ data: null }>('/notifications/read-all').then((r) => r.data.data),
        destroyRead: () => api.delete<{ data: { deleted: number } }>('/notifications/read').then((r) => r.data.data),
    },
    preferences: {
        get: () =>
            api
                .get<{ data: { categories: string[]; channels: string[]; matrix: NotificationPreferenceMatrix } }>('/notification-preferences')
                .then((r) => r.data.data),
        update: (matrix: NotificationPreferenceMatrix) =>
            api
                .put<{
                    data: { categories: string[]; channels: string[]; matrix: NotificationPreferenceMatrix };
                }>('/notification-preferences', { matrix })
                .then((r) => r.data.data),
    },
    search: (q: string) =>
        api
            .get<{ data: { term: string; total: number; groups: Record<string, SearchGroupItem[]> } }>('/search', { params: { q } })
            .then((r) => r.data.data),
    announcements: {
        mine: () => api.get<{ data: import('@/types/platform').AnnouncementItem[] }>('/announcements/mine').then((r) => r.data.data),
    },
    activityLogs: {
        index: (params: { page?: number; per_page?: number; log_name?: string; search?: string }) =>
            api.get<{ data: Paginated<ActivityLogEntry> }>('/activity-logs', { params }).then((r) => r.data.data),
        show: (id: number) => api.get<{ data: ActivityLogEntry }>(`/activity-logs/${id}`).then((r) => r.data.data),
        stats: () =>
            api
                .get<{
                    data: { total: number; today: number; unique_causers: number; log_names: { log_name: string; total: number }[] };
                }>('/activity-logs/stats')
                .then((r) => r.data.data),
    },
    admin: {
        dashboard: () => api.get<{ data: AdminDashboardSnapshot }>('/admin/dashboard').then((r) => r.data.data),
    },
    users: {
        index: (params: { page?: number; per_page?: number; search?: string; role?: string; status?: string }) =>
            api.get<{ data: Paginated<AdminUser> }>('/users', { params }).then((r) => r.data.data),
        show: (id: number) => api.get<{ data: AdminUser }>(`/users/${id}`).then((r) => r.data.data),
        store: (payload: UserInput) => api.post<{ data: AdminUser }>('/users', payload).then((r) => r.data.data),
        update: (id: number, payload: Partial<UserInput>) => api.put<{ data: AdminUser }>(`/users/${id}`, payload).then((r) => r.data.data),
        syncRoles: (id: number, roles: string[]) => api.put<{ data: AdminUser }>(`/users/${id}/roles`, { roles }).then((r) => r.data.data),
        toggleActive: (id: number) => api.patch<{ data: AdminUser }>(`/users/${id}/toggle-active`).then((r) => r.data.data),
        resetPassword: (id: number, password: string) =>
            api.post<{ data: null }>(`/users/${id}/reset-password`, { password }).then((r) => r.data.data),
        destroy: (id: number) => api.delete<{ data: null }>(`/users/${id}`).then((r) => r.data.data),
        roleOptions: () => api.get<{ data: { items: { name: string; label: string }[] } }>('/users/role-options').then((r) => r.data.data.items),
    },
    settings: {
        index: () => api.get<{ data: { groups: SettingsGroup[] } }>('/system-settings/grouped').then((r) => r.data.data.groups),
        update: (settings: Record<string, string>) =>
            api.put<{ data: { updated: Record<string, string> } }>('/system-settings/grouped', { settings }).then((r) => r.data.data),
    },
    reports: {
        catalog: () => api.get<{ data: { items: ReportDescriptor[]; context: ReportContext } }>('/reports').then((r) => r.data.data),
        download: (payload: { report: string; format: 'csv' | 'pdf'; academic_year_id?: number; academic_term_id?: number; section_id?: number }) =>
            api.post<Blob>('/reports/generate', payload, { responseType: 'blob' }).then((r) => {
                const disposition = r.headers['content-disposition'] as string | undefined;
                return { blob: r.data, filename: filenameFromDisposition(disposition) };
            }),
    },
    backups: {
        index: (params: { page?: number; per_page?: number }) =>
            api.get<{ data: Paginated<BackupItem> }>('/backups', { params }).then((r) => r.data.data),
        create: (payload: { type?: 'manual' | 'scheduled'; notes?: string }) =>
            api.post<{ data: BackupItem }>('/backups', payload).then((r) => r.data.data),
        download: (id: number) =>
            api.get<Blob>(`/backups/${id}/download`, { responseType: 'blob' }).then((r) => {
                const disposition = r.headers['content-disposition'] as string | undefined;
                return { blob: r.data, filename: filenameFromDisposition(disposition) };
            }),
        destroy: (id: number) => api.delete<{ data: null }>(`/backups/${id}`).then((r) => r.data.data),
    },
    health: () => api.get<{ data: SystemHealth }>('/system-health').then((r) => r.data.data),
};

export function filenameFromDisposition(disposition?: string): string {
    const match = disposition?.match(/filename\*?=(?:UTF-8''|")?([^";]+)/i);
    return match ? decodeURIComponent(match[1].replace(/"/g, '')) : `download-${Date.now()}`;
}

export function triggerDownload(blob: Blob, filename: string): void {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
}

export interface AdminUser {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    email_verified_at: string | null;
    is_active: boolean;
    school_profile_id: number | null;
    school: { id: number; name: string; short_name: string | null } | null;
    last_login_at: string | null;
    roles: { id: number; name: string; label: string; description: string | null }[];
    permissions: unknown[];
    created_at: string;
    updated_at: string;
}

export interface UserInput {
    name: string;
    email: string;
    password?: string;
    is_active?: boolean;
    roles?: string[];
    school_profile_id?: number | null;
}

export interface SettingsGroup {
    group: string;
    label: string;
    settings: {
        key: string;
        label: string;
        type: string;
        options: string[];
        value: string | null;
        is_public: boolean;
    }[];
}

export interface ReportDescriptor {
    key: string;
    label: string;
    group: string;
    columns: string[];
}

export interface ReportContext {
    academic_year_id: number | null;
    academic_year: string | null;
    academic_term_id: number | null;
    academic_term: string | null;
}

export interface BackupItem {
    id: number;
    file_name: string;
    size: number | null;
    size_human: string;
    status: string;
    type: string;
    created_by: string | null;
    notes: string | null;
    created_at: string;
}

export interface SystemHealth {
    app: { name: string; env: string; debug: boolean; url: string };
    database: { connection: string; connected: boolean };
    cache: { store: string };
    queue: { connection: string; pending_jobs: number };
    mail: { default: string };
    storage: { disk: string; backup_disk: string; backup_usage: number };
    disk_space: { free: number | null; total: number | null; free_human: string | null };
    time: string;
    last_backup: string | null;
}

export interface AdminDashboardSnapshot {
    context: {
        academic_year: { id: number; name: string } | null;
        academic_term: { id: number; name: string } | null;
    };
    counters: Record<string, number>;
    enrollment_status: { status: string; label: string; total: number }[];
    grade_status: { status: string; total: number }[];
    enrollment_trend: { month: string; label: string; enrollments: number; users: number }[];
    activity: {
        id: number;
        log_name: string;
        description: string;
        event: string | null;
        causer_name: string | null;
        created_at: string;
    }[];
}

export interface SearchGroupItem {
    id: number;
    label: string;
    subtitle: string;
    route: { name: string; params: Record<string, string | number> } | null;
}
