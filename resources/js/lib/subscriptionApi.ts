import api from '@/lib/api';
import type { Paginated } from '@/types/crud';

/**
 * Typed client for the Part 10 platform subscription & license management API.
 */

export interface TenantItem {
    id: number;
    school_profile_id: number | null;
    code: string;
    name: string;
    status: string;
    settings: Record<string, unknown> | null;
    school_profile?: { id: number; name: string } | null;
    subscription_count?: number;
    created_at: string;
    updated_at: string;
}

export interface PlanFeatureOption {
    value: string;
    label: string;
}

export interface SubscriptionPlanItem {
    id: number;
    name: string;
    code: string;
    description: string | null;
    billing_cycle: string;
    monthly_price: number;
    annual_price: number;
    trial_days: number | null;
    max_students: number | null;
    max_staff: number | null;
    max_branches: number | null;
    max_users: number | null;
    max_storage_mb: number | null;
    is_active: boolean;
    display_order: number;
    features: string[];
    created_at: string;
    updated_at: string;
}

export interface SubscriptionFeatureOverride {
    feature_code: string;
    is_enabled: boolean;
}

export interface SubscriptionItem {
    id: number;
    subscription_code: string;
    tenant_id: number;
    plan_id: number;
    status: string;
    start_date: string | null;
    expiration_date: string | null;
    trial_started_at: string | null;
    trial_ends_at: string | null;
    trial_status: string | null;
    billing_cycle: string;
    amount: number;
    auto_renewal: boolean;
    grace_days: number;
    grace_ends_at: string | null;
    expiration_behavior: string;
    last_renewed_at: string | null;
    cancelled_at: string | null;
    cancel_reason: string | null;
    suspended_at: string | null;
    suspend_reason: string | null;
    expected_resume_at: string | null;
    resumed_at: string | null;
    notes: string | null;
    days_remaining: number;
    allows_access: boolean;
    tenant: { id: number; code: string; name: string; status: string } | null;
    plan: { id: number; name: string; code: string; billing_cycle: string } | null;
    features: SubscriptionFeatureOverride[];
    created_at: string;
    updated_at: string;
}

export interface InvoiceItem {
    id: number;
    invoice_number: string;
    subscription_id: number;
    tenant_id: number;
    billing_period_start: string | null;
    billing_period_end: string | null;
    amount: number;
    discount: number;
    tax: number;
    total: number;
    currency: string;
    status: string;
    due_date: string | null;
    paid_at: string | null;
    payment_reference: string | null;
    payment_method: string | null;
    notes: string | null;
    paid_amount?: number;
    balance?: number;
    tenant: { id: number; name: string } | null;
    subscription: { id: number; subscription_code: string; plan: { id: number; name: string } | null } | null;
    created_at: string;
    updated_at: string;
}

export interface PaymentItem {
    id: number;
    invoice_id: number;
    subscription_id: number;
    tenant_id: number;
    amount: number;
    payment_date: string | null;
    payment_method: string;
    reference_number: string | null;
    status: string;
    recorded_by: number | null;
    notes: string | null;
    invoice: { id: number; invoice_number: string } | null;
    tenant: { id: number; name: string } | null;
    created_at: string;
    updated_at: string;
}

export interface LicenseItem {
    id: number;
    license_key: string;
    masked_key: string;
    revealed: boolean;
    tenant_id: number;
    plan_id: number;
    issued_date: string | null;
    start_date: string | null;
    expiration_date: string | null;
    status: string;
    max_users: number | null;
    max_students: number | null;
    max_branches: number | null;
    max_storage_mb: number | null;
    features: string[];
    created_by: number | null;
    updated_by: number | null;
    tenant: { id: number; name: string; code: string } | null;
    plan: { id: number; name: string; code: string } | null;
    created_at: string;
    updated_at: string;
}

export interface UsageSnapshotItem {
    id: number;
    tenant_id: number;
    subscription_id: number | null;
    period_year: number;
    period_month: number;
    students_count: number;
    users_count: number;
    staff_count: number;
    branches_count: number;
    storage_mb: number;
    documents_count: number;
    database_size_mb: number;
    captured_at: string | null;
    tenant: { id: number; name: string; code: string } | null;
    created_at: string;
    updated_at: string;
}

export interface LimitWarning {
    key: string;
    label: string;
    used: number;
    limit: number;
    percent: number;
}

export interface TenantUsage {
    snapshot: UsageSnapshotItem;
    limit_status: {
        usage: Record<string, number>;
        limits: Record<string, number | null>;
        warnings: LimitWarning[];
    };
    trend: UsageSnapshotItem[];
}

export interface AuditActionOption {
    value: string;
    label: string;
}

export interface AuditEntry {
    id: number;
    tenant_id: number | null;
    subscription_id: number | null;
    action: string;
    description: string | null;
    old_value: Record<string, unknown> | null;
    new_value: Record<string, unknown> | null;
    reason: string | null;
    actor_id: number | null;
    ip_address: string | null;
    tenant: { id: number; name: string; code: string } | null;
    subscription: { id: number; subscription_code: string } | null;
    created_at: string;
    updated_at: string;
}

export interface SubscriptionSettingItem {
    id: number;
    key: string;
    value: string | number | boolean | number[] | null;
    type: string;
    group: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface SubscriptionDashboard {
    metrics: Record<string, number>;
    plan_breakdown: Record<string, number>;
    expiring_soon: {
        id: number;
        subscription_code: string;
        status: string;
        expiration_date: string | null;
        days_remaining: number;
        tenant: { id: number; name: string } | null;
        plan: { id: number; name: string } | null;
    }[];
    recent_activity: {
        id: number;
        action: string;
        description: string;
        tenant: { id: number; name: string } | null;
        created_at: string;
    }[];
}

export const subscriptionApi = {
    dashboard: () => api.get<{ data: SubscriptionDashboard }>('/platform/dashboard').then((r) => r.data.data),

    tenants: {
        index: (params: { page?: number; per_page?: number; search?: string; status?: string }) =>
            api.get<{ data: Paginated<TenantItem> }>('/platform/tenants', { params }).then((r) => r.data.data),
        show: (id: number) => api.get<{ data: TenantItem }>(`/platform/tenants/${id}`).then((r) => r.data.data),
        store: (payload: Partial<TenantItem>) => api.post<{ data: TenantItem }>('/platform/tenants', payload).then((r) => r.data.data),
        update: (id: number, payload: Partial<TenantItem>) =>
            api.put<{ data: TenantItem }>(`/platform/tenants/${id}`, payload).then((r) => r.data.data),
        destroy: (id: number) => api.delete<{ data: null }>(`/platform/tenants/${id}`).then((r) => r.data.data),
        suspend: (id: number, reason?: string) =>
            api.post<{ data: TenantItem }>(`/platform/tenants/${id}/suspend`, { reason }).then((r) => r.data.data),
        resume: (id: number, reason?: string) =>
            api.post<{ data: TenantItem }>(`/platform/tenants/${id}/resume`, { reason }).then((r) => r.data.data),
    },

    plans: {
        index: (params: { page?: number; per_page?: number; search?: string }) =>
            api.get<{ data: Paginated<SubscriptionPlanItem> }>('/platform/plans', { params }).then((r) => r.data.data),
        options: () =>
            api
                .get<{
                    data: { id: number; name: string; code: string; billing_cycle: string; monthly_price: number; annual_price: number }[];
                }>('/platform/plans/options')
                .then((r) => r.data.data),
        featureCatalog: () => api.get<{ data: PlanFeatureOption[] }>('/platform/features/catalog').then((r) => r.data.data),
        store: (payload: Record<string, unknown>) => api.post<{ data: SubscriptionPlanItem }>('/platform/plans', payload).then((r) => r.data.data),
        update: (id: number, payload: Record<string, unknown>) =>
            api.put<{ data: SubscriptionPlanItem }>(`/platform/plans/${id}`, payload).then((r) => r.data.data),
        destroy: (id: number) => api.delete<{ data: null }>(`/platform/plans/${id}`).then((r) => r.data.data),
    },

    subscriptions: {
        index: (params: { page?: number; per_page?: number; search?: string; status?: string; tenant_id?: number; plan_id?: number }) =>
            api.get<{ data: Paginated<SubscriptionItem> }>('/platform/subscriptions', { params }).then((r) => r.data.data),
        show: (id: number) => api.get<{ data: SubscriptionItem }>(`/platform/subscriptions/${id}`).then((r) => r.data.data),
        provision: (payload: {
            tenant_id: number;
            plan_id: number;
            billing_cycle?: string;
            auto_renewal?: boolean;
            grace_days?: number;
            expiration_behavior?: string;
            notes?: string;
        }) => api.post<{ data: SubscriptionItem }>('/platform/subscriptions/provision', payload).then((r) => r.data.data),
        grant: (payload: {
            school_profile_id?: number;
            tenant_id?: number;
            plan_id: number;
            status?: string;
            billing_cycle?: string;
            start_date?: string;
            expiration_date?: string;
            amount?: number;
            auto_renewal?: boolean;
            grace_days?: number;
            expiration_behavior?: string;
            issue_license?: boolean;
            notes?: string;
        }) => api.post<{ data: SubscriptionItem }>('/platform/subscriptions/manual-grant', payload).then((r) => r.data.data),
        renew: (id: number) => api.post<{ data: SubscriptionItem }>(`/platform/subscriptions/${id}/renew`).then((r) => r.data.data),
        suspend: (id: number, reason?: string) =>
            api.post<{ data: SubscriptionItem }>(`/platform/subscriptions/${id}/suspend`, { reason }).then((r) => r.data.data),
        resume: (id: number, reason?: string) =>
            api.post<{ data: SubscriptionItem }>(`/platform/subscriptions/${id}/resume`, { reason }).then((r) => r.data.data),
        cancel: (id: number, reason?: string) =>
            api.post<{ data: SubscriptionItem }>(`/platform/subscriptions/${id}/cancel`, { reason }).then((r) => r.data.data),
        changePlan: (id: number, plan_id: number, reason?: string) =>
            api.post<{ data: SubscriptionItem }>(`/platform/subscriptions/${id}/change-plan`, { plan_id, reason }).then((r) => r.data.data),
        toggleFeature: (id: number, feature_code: string, is_enabled: boolean) =>
            api.post<{ data: SubscriptionItem }>(`/platform/subscriptions/${id}/features`, { feature_code, is_enabled }).then((r) => r.data.data),
        history: (id: number, params: { page?: number; per_page?: number }) =>
            api.get<{ data: Paginated<AuditEntry> }>(`/platform/subscriptions/${id}/history`, { params }).then((r) => r.data.data),
    },

    billing: {
        invoices: (params: { page?: number; per_page?: number; status?: string; with_balance?: boolean }) =>
            api.get<{ data: Paginated<InvoiceItem> }>('/platform/invoices', { params: { ...params, with_balance: true } }).then((r) => r.data.data),
        payments: (params: { page?: number; per_page?: number; status?: string }) =>
            api.get<{ data: Paginated<PaymentItem> }>('/platform/payments', { params }).then((r) => r.data.data),
        generate: (payload: {
            subscription_id: number;
            billing_period_start?: string;
            billing_period_end?: string;
            amount?: number;
            discount?: number;
            tax_rate?: number;
            due_date?: string;
            notes?: string;
        }) => api.post<{ data: InvoiceItem }>('/platform/invoices/generate', payload).then((r) => r.data.data),
        markPaid: (id: number, payload: { paid_at?: string; payment_reference?: string; payment_method?: string }) =>
            api.post<{ data: InvoiceItem }>(`/platform/invoices/${id}/mark-paid`, payload).then((r) => r.data.data),
        recordPayment: (payload: {
            invoice_id: number;
            amount: number;
            payment_method: string;
            reference_number?: string;
            payment_date?: string;
            notes?: string;
        }) => api.post<{ data: PaymentItem }>('/platform/payments', payload).then((r) => r.data.data),
    },

    licenses: {
        index: (params: { page?: number; per_page?: number; search?: string; status?: string; tenant_id?: number }) =>
            api.get<{ data: Paginated<LicenseItem> }>('/platform/licenses', { params }).then((r) => r.data.data),
        show: (id: number, reveal: boolean) =>
            api.get<{ data: LicenseItem }>(`/platform/licenses/${id}`, { params: reveal ? { reveal: 1 } : {} }).then((r) => r.data.data),
        regenerate: (id: number, reason?: string) =>
            api.post<{ data: LicenseItem }>(`/platform/licenses/${id}/regenerate`, { reason }).then((r) => r.data.data),
        revoke: (id: number, reason?: string) =>
            api.post<{ data: LicenseItem }>(`/platform/licenses/${id}/revoke`, { reason }).then((r) => r.data.data),
    },

    usage: {
        index: (params: { page?: number; per_page?: number; tenant_id?: number }) =>
            api.get<{ data: Paginated<UsageSnapshotItem> }>('/platform/usage', { params }).then((r) => r.data.data),
        tenant: (tenantId: number) => api.get<{ data: TenantUsage }>(`/platform/usage/tenants/${tenantId}`).then((r) => r.data.data),
        snapshot: (tenantId: number) =>
            api.post<{ data: UsageSnapshotItem }>(`/platform/usage/tenants/${tenantId}/snapshot`).then((r) => r.data.data),
    },

    features: {
        catalog: () => api.get<{ data: PlanFeatureOption[] }>('/platform/features/catalog').then((r) => r.data.data),
        tenant: (tenantId: number) =>
            api
                .get<{
                    data: { tenant_id: number; features: string[]; limits: Record<string, number | null>; subscription: SubscriptionItem | null };
                }>(`/platform/features/tenants/${tenantId}`)
                .then((r) => r.data.data),
    },

    audit: {
        index: (params: { page?: number; per_page?: number; action?: string; tenant_id?: number }) =>
            api
                .get<{
                    data: Paginated<AuditEntry>;
                }>('/platform/audit', { params: { ...params, filter: params.action ? { action: params.action } : undefined } })
                .then((r) => r.data.data),
        show: (id: number) => api.get<{ data: AuditEntry }>(`/platform/audit/${id}`).then((r) => r.data.data),
        actions: () => api.get<{ data: AuditActionOption[] }>('/platform/audit/actions').then((r) => r.data.data),
    },

    settings: {
        grouped: (schoolProfileId?: number | null) =>
            api
                .get<{ data: Record<string, SubscriptionSettingItem[]> }>('/platform/settings/grouped', {
                    params: schoolProfileId ? { school_profile_id: schoolProfileId } : {},
                })
                .then((r) => r.data.data),
        bulk: (settings: Record<string, string | number | boolean | number[] | null>, schoolProfileId?: number | null) =>
            api
                .put<{ data: Record<string, SubscriptionSettingItem[]> }>('/platform/settings/bulk', {
                    settings,
                    ...(schoolProfileId ? { school_profile_id: schoolProfileId } : {}),
                })
                .then((r) => r.data.data),
    },
};
