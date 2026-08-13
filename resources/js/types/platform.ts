export interface AppNotification {
    id: string;
    type: string;
    title: string;
    body: string;
    category: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
}

export interface NotificationPreferenceMatrix {
    [category: string]: {
        database: boolean;
        email: boolean;
    };
}

export interface ChildSummary {
    id: number;
    name: string;
    first_name: string;
    middle_name: string;
    last_name: string;
    extension_name: string | null;
    student_number: string | null;
    lrn: string | null;
    gender: string | null;
    birth_date: string | null;
    status: string;
    enrollment_status: string | null;
    enrollment_status_label: string | null;
    academic_year: string | null;
    academic_term: string | null;
    grade_level: string | null;
    section: string | null;
    campus: string | null;
    adviser: string | null;
    academic_summary: AcademicSummary;
    modules: Record<string, boolean>;
}

export interface AcademicSummary {
    records: GradeSummary[];
    total_units: number;
    published_records: number;
    general_average: number | null;
}

export interface GradeSummary {
    id: number;
    subject: string | null;
    subject_code: string | null;
    units: number;
    raw_grade: number | null;
    final_grade: number | null;
    remarks: string | null;
    status: string;
    term: string | null;
}

export interface PortalScheduleEntry {
    id: number;
    subject: string | null;
    subject_code: string | null;
    teacher: string | null;
    room: string | null;
    day: string;
    start_time: string | null;
    end_time: string | null;
}

export interface PortalEnrollment {
    id: number;
    enrollment_number: string;
    reference_number: string | null;
    status: string;
    status_label: string;
    academic_year: string | null;
    academic_term: string | null;
    grade_level: string | null;
    section: string | null;
    campus: string | null;
    enrollment_date: string | null;
    date_enrolled: string | null;
}

export interface PortalDocument {
    id: number;
    name: string;
    document_type: string;
    status: string;
    created_at: string;
    url: string | null;
}

export interface AnnouncementItem {
    id: number;
    title: string;
    content: string;
    category: string | null;
    priority: string | null;
    status: string | null;
    scheduled_at: string | null;
    author: { id: number; name: string } | null;
    published_at: string | null;
}

export interface ActivityLogEntry {
    id: number;
    log_name: string;
    description: string;
    event: string | null;
    causer_id: number | null;
    causer_name: string | null;
    properties: Record<string, unknown>;
    created_at: string;
}
