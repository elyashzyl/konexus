# Demo Credentials

All demo accounts share the password: **`password`**

Seeded by `Database\Seeders\DemoUsersSeeder` (plus `admin@konexus.local` from `DatabaseSeeder`). The student/parent accounts are linked to their registrar records, so their portals are fully populated.

## Portals

| Portal | Role(s) | Account | Email |
| --- | --- | --- | --- |
| Student Portal | student | Student 1 | `demo.student.1@konexus.local` |
| Student Portal | student | Student 2 | `demo.student.2@konexus.local` |
| Student Portal | student | Student 3 | `demo.student.3@konexus.local` |
| Parent Portal | parent | Parent 1 | `demo.parent.1@konexus.local` |
| Parent Portal | parent | Parent 2 | `demo.parent.2@konexus.local` |
| Teacher Portal | teacher | Teacher | `demo.teacher@konexus.local` |
| Teacher Portal | adviser | Adviser | `demo.adviser@konexus.local` |
| Staff Portal | principal | `demo.principal@konexus.local` |
| Staff Portal | registrar | `demo.registrar@konexus.local` |
| Staff Portal | finance-officer | `demo.finance-officer@konexus.local` |
| Staff Portal | guidance-counselor | `demo.guidance-counselor@konexus.local` |
| Staff Portal | school-nurse | `demo.school-nurse@konexus.local` |
| Staff Portal | librarian | `demo.librarian@konexus.local` |
| Staff Portal | hr-officer | `demo.hr-officer@konexus.local` |
| Staff Portal | inventory-officer | `demo.inventory-officer@konexus.local` |

## Admin

| Role | Email |
| --- | --- |
| super-administrator | `admin@konexus.local` |
| school-administrator | `demo.school-admin@konexus.local` |

## Staff (records & operations)

Each staff role gets its own portal page (`/portal/staff/<role>`) with a role-specific overview; announcements are shared via the existing `/announcements/mine` feed. After logging in, staff land directly on their portal and never see the School/Admin pages — only `super-administrator` and `school-administrator` do.

| Role | Email |
| --- | --- |
| principal | `demo.principal@konexus.local` |
| registrar | `demo.registrar@konexus.local` |
| finance-officer | `demo.finance-officer@konexus.local` |
| guidance-counselor | `demo.guidance-counselor@konexus.local` |
| school-nurse | `demo.school-nurse@konexus.local` |
| librarian | `demo.librarian@konexus.local` |
| hr-officer | `demo.hr-officer@konexus.local` |
| inventory-officer | `demo.inventory-officer@konexus.local` |

> Note: the student/parent email suffix numbers come from database IDs, so `demo.student.N` / `demo.parent.N` reflect the first few records created by the registrar seeder.

## Re-seed

To recreate or refresh every account:

```bash
php artisan migrate:fresh --seed
# or, to only re-run the demo accounts on an existing database:
php artisan db:seed --class=DemoUsersSeeder
```
