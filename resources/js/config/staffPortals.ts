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
    modules: StaffPortalSection[];
}

export const STAFF_PORTALS: StaffPortalConfig[] = [
    {
        role: 'principal',
        label: 'School Principal',
        eyebrow: 'Leadership office',
        description: 'Academic leadership and oversight for the whole school.',
        intro: 'Your principal workspace keeps the pulse of the school close at hand — announcements, calendar, and the records that matter for instructional leadership.',
        modules: [
            { key: 'announcements', title: 'Announcements', description: 'School-wide notices and targeted updates.' },
            { key: 'calendar', title: 'School calendar', description: 'Holidays, examinations and school events.' },
            { key: 'reports', title: 'Reports', description: 'Operational snapshots prepared by the office.' },
        ],
    },
    {
        role: 'registrar',
        label: 'Registrar',
        eyebrow: 'Records office',
        description: 'Enrollment records, documents and academic history.',
        intro: 'Your registrar workspace anchors the records office — enrollments, private documents, and the school calendar that structures every term.',
        modules: [
            { key: 'enrollments', title: 'Enrollments', description: 'Enrollment history and academic records.' },
            { key: 'documents', title: 'Private documents', description: 'Issued and archived student documents.' },
            { key: 'calendar', title: 'School calendar', description: 'Holidays, examinations and school events.' },
        ],
    },
    {
        role: 'finance-officer',
        label: 'Finance Officer',
        eyebrow: 'Finance office',
        description: 'Fees, collections and financial operations.',
        intro: 'Your finance workspace keeps you aligned with billing cycles, payment statuses, and the announcements your office publishes to parents and students.',
        modules: [
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
        modules: [
            { key: 'announcements', title: 'Announcements', description: 'School-wide notices and targeted updates.' },
            { key: 'calendar', title: 'School calendar', description: 'Events and examinations to plan around.' },
            { key: 'facilities', title: 'Facilities', description: 'Buildings and rooms across the campus.' },
        ],
    },
];

export const staffPortalByRole = (role: string): StaffPortalConfig | undefined => STAFF_PORTALS.find((portal) => portal.role === role);
