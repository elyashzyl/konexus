import api from '@/lib/api';
import type { AcademicSummary, AnnouncementItem, ChildSummary, PortalDocument, PortalEnrollment, PortalScheduleEntry } from '@/types/platform';

export const portalApi = {
    student: {
        dashboard: () => api.get<{ data: { profile: ChildSummary | null; announcements: AnnouncementItem[]; modules: Record<string, boolean> } }>('/portal/student/dashboard').then((r) => r.data.data),
        schedule: () => api.get<{ data: { items: PortalScheduleEntry[] } }>('/portal/student/schedule').then((r) => r.data.data.items),
        grades: () => api.get<{ data: AcademicSummary }>('/portal/student/grades').then((r) => r.data.data),
        enrollments: () => api.get<{ data: { items: PortalEnrollment[] } }>('/portal/student/enrollments').then((r) => r.data.data.items),
        documents: () => api.get<{ data: { items: PortalDocument[] } }>('/portal/student/documents').then((r) => r.data.data.items),
    },
    parent: {
        dashboard: () => api.get<{ data: { parent: { name: string; email: string; contact_number: string } | null; children: ChildSummary[]; announcements: AnnouncementItem[]; modules: Record<string, boolean> } }>('/portal/parent/dashboard').then((r) => r.data.data),
        children: () => api.get<{ data: { items: ChildSummary[] } }>('/portal/parent/children').then((r) => r.data.data.items),
        child: (id: number) => api.get<{ data: ChildSummary }>(`/portal/parent/children/${id}`).then((r) => r.data.data),
        schedule: (id: number) => api.get<{ data: { items: PortalScheduleEntry[] } }>(`/portal/parent/children/${id}/schedule`).then((r) => r.data.data.items),
        grades: (id: number) => api.get<{ data: AcademicSummary }>(`/portal/parent/children/${id}/grades`).then((r) => r.data.data),
        enrollments: (id: number) => api.get<{ data: { items: PortalEnrollment[] } }>(`/portal/parent/children/${id}/enrollments`).then((r) => r.data.data.items),
        documents: (id: number) => api.get<{ data: { items: PortalDocument[] } }>(`/portal/parent/children/${id}/documents`).then((r) => r.data.data.items),
    },
    teacher: {
        dashboard: () => api.get<{ data: { teacher: { id: number; name: string; employee_number: string | null; specialization: string | null; department: string | null; advisory_section: string | null }; academic_year: string | null; academic_term: string | null; stats: Record<string, number>; modules: Record<string, boolean> } }>('/portal/teacher/dashboard').then((r) => r.data.data),
        assignments: () => api.get<{ data: { items: TeacherAssignmentItem[] } }>('/portal/teacher/assignments').then((r) => r.data.data.items),
        schedule: () => api.get<{ data: { items: PortalScheduleEntry[] } }>('/portal/teacher/schedule').then((r) => r.data.data.items),
        advisoryClass: () => api.get<{ data: { class: { id: number; name: string; section: string | null; students: AdvisoryStudent[] } | null } }>('/portal/teacher/advisory-class').then((r) => r.data.data.class),
        roster: (sectionId: number) => api.get<{ data: TeacherRoster }>(`/portal/teacher/sections/${sectionId}/roster`).then((r) => r.data.data),
        students: () => api.get<{ data: { items: PortalStudentRow[] } }>('/portal/teacher/students').then((r) => r.data.data.items),
    },
};

export interface TeacherAssignmentItem {
    id: number;
    section_id: number;
    section: string | null;
    grade_level: string | null;
    subject_id: number;
    subject: string | null;
    subject_code: string | null;
    campus: string | null;
    term: string | null;
    units: number;
}

export interface AdvisoryStudent {
    id: number;
    name: string;
    student_number: string | null;
    lrn: string | null;
    gender: string | null;
}

export interface TeacherRoster {
    section_id: number;
    subject_id: number | null;
    subject_offering_id: number | null;
    subject: string | number | null;
    offering_units: number | null;
    items: RosterStudent[];
}

export interface RosterStudent {
    student_id: number;
    name: string;
    student_number: string | null;
    lrn: string | null;
    gender: string | null;
    grade_record_id: number | null;
    final_grade: number | null;
    status: string;
}

export interface PortalStudentRow {
    id: number;
    name: string;
    student_number: string | null;
    lrn: string | null;
    gender: string | null;
    section: string | null;
}
