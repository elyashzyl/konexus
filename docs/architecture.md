# KONEXUS — Technical Blueprint

**Project:** KOAMISHIN School Management Information System (SMIS)
**Phase:** Foundation (no business modules yet)
**Status:** Implemented and verified — the blueprint below is the contract for the foundation and the extension path for all future modules.

Companion report: [`docs/architecture-analysis.md`](./architecture-analysis.md) (verified implementation details).

---

## 1. Analysis of the EnvKit Starter Kit

The EnvKit Starter Kit shipped a Laravel 12 + Inertia + Vue 3 application with session/cookie auth, Ziggy, and a shadcn-vue/Tailwind UI kit. The KONEXUS foundation was built by **extending and converting** that starter — never recreating it:

- **Converted** the frontend from an Inertia/SSR app to a **pure Vue Router SPA**: `app.ts` bootstraps Pinia + Vue Router + TanStack Vue Query; `routes/web.php` is a catch-all that renders the SPA shell; `resources/views/app.blade.php` is the shell.
- **Kept and rebranded** the shadcn-vue component library, Tailwind config, layout primitives (`AppShell`, `AppSidebar`, `AppHeader`, settings/auth layouts), and the appearance (theme) composable.
- **Replaced** the Inertia auth pages with SPA pages wired to a Pinia `auth` store backed by a new Sanctum REST API.
- **Removed** dead Inertia routes/controllers from the request cycle (`routes/auth.php`, `routes/settings.php` are no longer loaded; `HandleInertiaRequests` is unregistered).
- **Upgraded** the model layer: `User` gained Sanctum tokens + Spatie roles; `Role`/`Permission` extend the Spatie models; 14-role `RoleEnum` added.

### Starter inventory (retained)
`resources/js/components/ui/*` (radix-vue primitives), `layouts/*`, `composables/useAppearance.ts`, `tailwind.config.js`, `resources/css/app.css`, ESLint/Prettier config.

---

## 2. Recommended Project Architecture

KONEXUS is a **decoupled REST API + SPA** on a single Laravel deployment (same origin in production; Vite dev proxy in development).

```
┌─────────────────────────────┐        ┌─────────────────────────────┐
│  Browser (Vue 3 SPA)        │  HTTP  │  Laravel 12 REST API        │
│  Vue Router + Pinia         │ ─────▶ │  /api/v1/* (Sanctum token)  │
│  Axios (bearer token)       │        │  Controller → Service       │
│  TanStack Vue Query         │ ◀───── │  → Repository → Eloquent     │
│  Zod (forms)                │        │  FormRequest → Resource      │
└──────────────┬──────────────┘        └──────────────┬──────────────┘
               │                                      │
               │  Dev: Vite 5173 HTTPS proxy → 8000   │  SQLite (dev) / MySQL 8 (prod)
               │  Prod: Laravel serves SPA shell      │
               └──────────────────────────────────────┘
```

**Key decisions**

| Decision | Choice | Why |
|---|---|---|
| Transport | SPA served by Laravel; API JSON | One deployable unit; no CORS in prod; SSR not needed |
| Auth transport | Sanctum bearer tokens (not cookies) | Clean mobile/desktop client story; matches REST API spec |
| State split | Pinia for client auth state; TanStack Query for server data | Auth is one global concern; module data is cached/queryable |
| Validation | Zod on client, FormRequest on server | Type-safe forms + authoritative server validation |
| DB | SQLite locally, MySQL 8 in production | Portable migrations; zero local setup |
| Folder strategy | Modular by feature (modules/) with shared kernel | Business modules stay self-contained; cross-module code lives in shared/ |

---

## 3. Backend Folder Structure

```
app/
├── Enums/            # RoleEnum (14 system roles + metadata)
├── Exceptions/       # ApiException (typed HTTP exceptions)
├── Support/          # ApiResponse envelope + fromThrowable mapping
├── Traits/           # ApiResponseTrait (controller response helpers)
├── Helpers/          # api_success, api_error, is_api_request, frontend_url
├── Models/           # User, Role, Permission (+ future module models)
├── Repositories/
│   ├── Contracts/    # RepositoryInterface, UserRepositoryInterface
│   ├── BaseRepository.php
│   └── UserRepository.php
├── Services/         # AuthService (business rules live here)
├── Providers/        # App (policies/gates), Repository, RateLimit
├── Http/
│   ├── Controllers/Api/V1/   # ApiController, Auth/{Auth,Password,Session}, Role
│   ├── Middleware/           # Authenticate, EnsureUserHasRole
│   ├── Requests/             # Auth/* (Login, Register, Forgot, Reset, Profile, Password)
│   └── Resources/            # AuthResource, UserResource, RoleResource, PermissionResource
├── Notifications/    # ResetPasswordNotification (frontend links)
├── Policies/         # UserPolicy
└── Modules/          # (future) one namespace+dir per module
```

**Layering rule:** Controllers stay thin; services hold business rules; repositories own persistence; models stay lean. Controllers depend on services; services depend on repository **contracts** (constructor-injected, bound in `RepositoryServiceProvider`).

**Future module pattern (per module):**
```
app/Modules/Academics/
├── Http/Controllers/Api/V1/
├── Http/Requests/
├── Http/Resources/
├── Services/
├── Repositories/
├── Models/
├── Policies/
└── routes.php          # mounted into routes/api.php
```

---

## 4. Frontend Folder Structure

```
resources/js/
├── app.ts               # bootstrap: Pinia + Router + VueQuery + theme init
├── App.vue              # <RouterView/> + <Toaster/>
├── modules/             # module registry (routes wired into the app router)
│   └── index.ts         # export const moduleRoutes: RouteRecordRaw[]
├── router/              # routes, guards (requiresAuth/requiresGuest), title sync
├── stores/              # Pinia: auth.ts
├── lib/                 # api.ts (Axios client), utils.ts
├── schemas/             # auth.ts (Zod)
├── constants/           # app.ts (brand + route constants)
├── types/               # index.ts (API + domain types)
├── composables/         # useAppearance, useInitials
├── layouts/             # AppLayout, AuthLayout, app/*, auth/*, settings/Layout
├── pages/               # Dashboard, auth/*, settings/*, errors/*
├── components/          # AppShell, AppHeader, AppSidebar, Nav*, ui/*
└── shared/              # (future) cross-module components/utils
```

**Future module pattern (per module):**
```
resources/js/modules/academics/
├── index.ts             # module manifest + route registration
├── routes.ts            # RouteRecordRaw[] (lazy-loaded pages)
├── api/                 # service functions using the shared Axios client
├── stores/              # module Pinia stores
├── schemas/             # Zod schemas
├── types/               # module types
├── composables/
├── components/          # module-specific components
└── pages/
```

**Lazy-loading contract:** every module page is imported with `() => import(...)` so the production bundle splits per module.

---

## 5. Authentication Architecture

**Transport:** Sanctum **personal access tokens** (Bearer). Token stored in `localStorage` under `konexus_token`; an Axios request interceptor injects `Authorization: Bearer <token>`.

**Flow (login):**
1. `Login.vue` → Zod validate → `auth.login()` (Pinia)
2. `POST /api/v1/auth/login` (throttle:login) → `AuthService::attemptLogin()`
3. Service: load user by email → verify password hash → reject inactive accounts (`ApiException::forbidden`) → stamp `last_login_at` → issue token
4. Response `AuthResource { token, token_type, expires_in, user }`; store persists token + user
5. Router guard sees `requiresAuth` satisfied → redirect to intended `redirect` query or dashboard

**Session management:** `GET/POST /auth/me`, `POST /auth/logout` (revoke current token), `GET /auth/sessions`, `DELETE /auth/sessions` (revoke all but current), `DELETE /auth/sessions/{token}`.

**Recovery:** `POST /auth/forgot-password` + `POST /auth/reset-password` (throttle:password). `ResetPasswordNotification` emails a link to the **SPA** (`FRONTEND_URL/auth/reset-password?token=…&email=…`).

**401 handling:** the Axios response interceptor calls a registered handler (`setUnauthorizedHandler`) that clears the store so guards bounce to `/auth/login`.

**Token policy:** `SANCTUM_TOKEN_EXPIRATION` (default 10080 min), `konexus_` prefix.

---

## 6. Role Architecture

**Source of truth:** `App\Enums\RoleEnum` — 14 system roles, each with `key`, `label`, `description`.

| Role | Domain |
|---|---|
| super-administrator | Platform-wide |
| school-administrator | School operations |
| principal, registrar, finance-officer | Academic/records/finance |
| teacher, adviser | Classroom |
| guidance-counselor, school-nurse | Student welfare |
| librarian, inventory-officer, hr-officer | Support services |
| student, parent | Portals |

**Persistence:** Spatie Laravel Permission. Roles table extended with `label`/`description`. `RolesAndPermissionsSeeder` is idempotent (`firstOrCreate`) and also provisions the super admin from `SUPER_ADMIN_*` env (`admin@konexus.local` / `password` by default).

**Exposure:**
- `GET /api/v1/roles` (auth) — full role list via `RoleResource`.
- `GET /api/v1/roles/catalog` (public) — lightweight `{key, label, description}` used by registration/dropdowns.

**Enforcement (ready for modules):**
- Middleware aliases: `roles`, `role`, `permission`, `role_or_permission` (Spatie).
- Gates: `manage-users`, `manage-roles`, `manage-settings`, `view-module`.
- `UserPolicy` for model-level authorization.
- Frontend: `auth.primaryRole` and `auth.can(role)` for conditional UI.

**Permissions catalog** (specific actions, e.g. `academics.view-classes`) is intentionally **deferred** to the module phase — roles exist now; fine-grained permissions will be defined per module and bound to roles then.

---

## 7. Routing Architecture

**Client (vue-router):** single router with route-level layouts.

| Route | Layout | Guard |
|---|---|---|
| `/` (dashboard) | `AppLayout` (sidebar shell) | `requiresAuth` |
| `/settings/{profile,password,appearance}` | `AppLayout` → `SettingsLayout` | `requiresAuth` |
| `/modules/*` (future) | `AppLayout` | `requiresAuth` |
| `/auth/{login,register,forgot-password,reset-password}` | `AuthLayout` | `requiresGuest` |
| `/403`, `/404`, `/500` | full-page | none |
| `/:pathMatch(.*)*` | → redirect `/404` | none |

**Guards (`router.beforeEach`):**
1. Ensure the auth store is initialized (`auth.initialize()` — fetch `/auth/me` if a token exists).
2. `requiresAuth` + not authenticated → `/auth/login?redirect=<current>`.
3. `requiresGuest` + authenticated → dashboard.
4. `afterEach` syncs `document.title` from route meta.

**Server (Laravel):**
- `/` → SPA shell (`view('app')`) named `home`.
- `/login` → SPA shell named `login` (target of the web `Authenticate` middleware redirect).
- `/{any}` catch-all → SPA shell, **except** `api/*` which returns a structured JSON 404.
- `/api/v1/*` → REST API (see §8).

---

## 8. API Architecture

**Versioning:** URL prefix `/api/v1`, route names `api.v1.*`.

**Envelope (uniform):**
```json
{ "success": true, "message": "…", "data": { … } | null, "errors": { "field": ["msg"] } | null }
```
Every success and every failure (validation 422, auth 401, forbidden 403, not found 404, rate limit 429, 500) flows through `App\Support\ApiResponse` via `bootstrap/app.php` exception rendering — no HTML or framework-default JSON leaks from API paths.

**Endpoints (foundation, verified):**

| Method | URI | Auth |
|---|---|---|
| POST | `/api/v1/auth/login` | public (throttle) |
| POST | `/api/v1/auth/register` | public (throttle) |
| POST | `/api/v1/auth/forgot-password` | public (throttle) |
| POST | `/api/v1/auth/reset-password` | public (throttle) |
| GET | `/api/v1/auth/me` | sanctum |
| PATCH | `/api/v1/auth/me` | sanctum |
| DELETE | `/api/v1/auth/me` | sanctum |
| PUT | `/api/v1/auth/password` | sanctum |
| POST | `/api/v1/auth/logout` | sanctum |
| GET | `/api/v1/auth/sessions` | sanctum |
| DELETE | `/api/v1/auth/sessions` | sanctum |
| DELETE | `/api/v1/auth/sessions/{token}` | sanctum |
| GET | `/api/v1/roles` | sanctum |
| GET | `/api/v1/roles/catalog` | public |

**Conventions for future modules:** `GET` list + `GET` item + `POST` create + `PATCH` update + `DELETE` destroy; `{id}` route params; FormRequest validation; JSON resource responses; `paginated()` helper for collections; nested module routes under `modules/<module>`.

---

## 9. Module Architecture

**Registration point:** `resources/js/modules/index.ts` exports `moduleRoutes` (spread into the app router under the authenticated `AppLayout` shell). Each module registers its lazy-loaded pages there.

**Suggested modules (phase 2+, in dependency order):**

| Module | Purpose |
|---|---|
| `core` | school year/term, school profile, campus/config |
| `academics` | subjects, sections, classes, grades |
| `registrar` | enrollments, student records, documents |
| `finance` | billing, assessments, payments |
| `guidance` | counseling, behavior, welfare |
| `clinic` | health records, medical clearances |
| `library` | catalog, borrowing |
| `hr` | employee records, leave |
| `inventory` | assets and supplies |
| `reports` | cross-module exports (DomPDF/Excel) |

**Per-module slice** (mirror in `app/Modules/<name>` and `resources/js/modules/<name>`):
- Routes (client + server), models/migrations, services, repositories, requests, resources, policies.
- A gate + `permission` middleware pair guarding its endpoints; UI gated by `auth.can(...)` / `view-module` gate.

**Contract:** modules may depend on `shared/` and the kernel (auth, roles, API client, UI kit) but not on each other. Cross-module data flows through shared services/APIs.

---

## 10. Development Roadmap

**Phase 1 — Foundation (DONE, verified):** REST API + envelopes + error mapping; Sanctum auth + sessions + password reset; 14 roles + super admin seeding; repository/service layering; SPA conversion (router, guards, Pinia auth, Zod forms, error pages); KONEXUS design system; 26 backend tests green; `pint`/`eslint`/`vue-tsc`/`vite build` all pass.

**Phase 2 — Permissions & core config:** define a permissions catalog per module; bind permissions to roles; add gates/middleware enforcement; school-year/term and school profile module; tenant-safe queries.

**Phase 3 — Registrar & academics:** student profiles, enrollments, sections, subjects, classes, grading; student/parent portal basics.

**Phase 4 — Operations:** finance (billing/payments), guidance, clinic, library, HR, inventory modules.

**Phase 5 — Reporting:** DomPDF invoices/certificates (installed) and Excel exports (maatwebsite/excel — pinned to a PHP 8.5-compatible release when available).

**Phase 6 — Hardening & rollout:** email verification, 2FA, audit-review UI, activity-log retention, caching, queue workers, MySQL 8 provisioning, deployment (TLS, same-origin build).

### Verification commands
```bash
php artisan migrate:fresh --seed
php artisan test
php artisan pint --test
npm run types && npm run lint && npm run build
```
