import type { LucideIcon } from 'lucide-vue-next';
import { Banknote, BookOpen, ClipboardList, HeartHandshake, HeartPulse, Landmark, Package, UserCog } from 'lucide-vue-next';

export interface StaffPortalSection {
    key: string;
    title: string;
    description: string;
}

export interface StaffPortalConfig {
    role: string;
    label: string;
    eyebrow: string;
    description: string;
    intro: string;
    icon: LucideIcon;
    modules: StaffPortalSection[];
}

export const STAFF_PORTALS: StaffPortalConfig[] = [
    {
        role: 'principal',
        label: 'School Principal',
        eyebrow: 'Leadership office',
        description: 'Academic leadership and oversight for the whole school.',
        intro: 'Your principal workspace keeps the pulse of the school close at hand — announcements, calendar, and the records that matter for instructional leadership.',
        icon: Landmark,
        modules: [
            { key: 'announcements', title: 'Announcements', description: 'School-wide notices and targeted updates.' },
            { key: 'calendar', title: 'School calendar', description: 'Holidays, examinations and school events.' },
            { key: 'reports', title: 'Reports', description: 'Operational snapshots prepared by the office.' },
            { key: 'enrollment-approvals', title: 'Section assignment', description: 'Assign paid elementary and high school learners to sections and classes.' },
        ],
    },
    {
        role: 'registrar',
        label: 'Registrar',
        eyebrow: 'Records office',
        description: 'Enrollment records, documents and academic history.',
        intro: 'Your registrar workspace anchors the records office — enrollments, private documents, and the school calendar that structures every term.',
        icon: ClipboardList,
        modules: [
            {
                key: 'enrollment-operations',
                title: 'Enrollment desk',
                description: 'Manage elementary and high school applications from form to payment to section assignment.',
            },
            {
                key: 'enrollments',
                title: 'Enrollment data',
                description: 'Browse the full enrollment records ledger across academic years.',
            },
            {
                key: 'students',
                title: 'Students',
                description: 'Look up learner records, identities and contact details.',
            },
            {
                key: 'notifications',
                title: 'Notification center',
                description: 'Review enrollment moves, announcements and system updates.',
            },
            {
                key: 'online-enrollment',
                title: 'Public enrollment journey',
                description: 'Open the guided online application families use before office review.',
            },
            { key: 'announcements', title: 'Announcements', description: 'Publish the notices applicants and families need.' },
        ],
    },
    {
        role: 'finance-officer',
        label: 'Finance Officer',
        eyebrow: 'Finance office',
        description: 'Fees, collections and financial operations.',
        intro: 'Your finance workspace keeps you aligned with billing cycles, payment statuses, and the announcements your office publishes to parents and students.',
        icon: Banknote,
        modules: [
            { key: 'enrollment-payments', title: 'Enrollment payments', description: 'Mark online or cash tuition as paid so the principal can assign a section.' },
            { key: 'payments', title: 'Payments', description: 'Fees and collection tracking.' },
            { key: 'statuses', title: 'Payment statuses', description: 'Reference statuses used across enrollments.' },
            { key: 'announcements', title: 'Announcements', description: 'School-wide notices and targeted updates.' },
        ],
    },
    {
        role: 'guidance-counselor',
        label: 'Guidance Counselor',
        eyebrow: 'Guidance office',
        description: 'Learner support and well-being programs.',
        intro: 'Your guidance workspace centers learner well-being — announcements, calendar events, and the reference lists your office maintains.',
        icon: HeartHandshake,
        modules: [
            { key: 'announcements', title: 'Announcements', description: 'School-wide notices and targeted updates.' },
            { key: 'calendar', title: 'School calendar', description: 'Events and examinations to plan around.' },
            { key: 'master-data', title: 'Reference lists', description: 'Religions, conditions and other master data.' },
        ],
    },
    {
        role: 'school-nurse',
        label: 'School Nurse',
        eyebrow: 'Clinic',
        description: 'Health services and wellness records.',
        intro: 'Your clinic workspace keeps health matters visible — announcements, calendar events, and the medical reference lists your office relies on.',
        icon: HeartPulse,
        modules: [
            { key: 'announcements', title: 'Announcements', description: 'School-wide notices and targeted updates.' },
            { key: 'calendar', title: 'School calendar', description: 'Events and examinations to plan around.' },
            { key: 'medical', title: 'Health references', description: 'Medical conditions and hospital lists.' },
        ],
    },
    {
        role: 'librarian',
        label: 'Librarian',
        eyebrow: 'Library',
        description: 'Learning resources and the library program.',
        intro: 'Your library workspace connects the learning commons to the school — announcements, calendar, and the subject lists that shape collections.',
        icon: BookOpen,
        modules: [
            { key: 'announcements', title: 'Announcements', description: 'School-wide notices and targeted updates.' },
            { key: 'calendar', title: 'School calendar', description: 'Events and examinations to plan around.' },
            { key: 'subjects', title: 'Subject lists', description: 'Departments and subjects offered.' },
        ],
    },
    {
        role: 'hr-officer',
        label: 'HR Officer',
        eyebrow: 'Human resources',
        description: 'Personnel records and school staffing.',
        intro: 'Your HR workspace keeps personnel matters aligned — announcements, calendar events, and the staff structure your office administers.',
        icon: UserCog,
        modules: [
            { key: 'announcements', title: 'Announcements', description: 'School-wide notices and targeted updates.' },
            { key: 'calendar', title: 'School calendar', description: 'Events and examinations to plan around.' },
            { key: 'staff', title: 'Staff records', description: 'Employee numbers and support areas.' },
        ],
    },
    {
        role: 'inventory-officer',
        label: 'Inventory Officer',
        eyebrow: 'Property & supply',
        description: 'Facilities, rooms and school property.',
        intro: 'Your property workspace keeps facilities in view — announcements, calendar events, and the buildings and rooms your office tracks.',
        icon: Package,
        modules: [
            { key: 'announcements', title: 'Announcements', description: 'School-wide notices and targeted updates.' },
            { key: 'calendar', title: 'School calendar', description: 'Events and examinations to plan around.' },
            { key: 'facilities', title: 'Facilities', description: 'Buildings and rooms across the campus.' },
        ],
    },
];

export const staffPortalByRole = (role: string): StaffPortalConfig | undefined => STAFF_PORTALS.find((portal) => portal.role === role);
