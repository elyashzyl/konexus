# KONEXUS — Architecture Analysis Report

**Project:** KOAMISHIN School Management Information System (SMIS)
**Version:** 1.0 (Foundation)
**Status:** Verified working end-to-end

---

## 1. Executive Summary

KONEXUS is a full-stack School Management Information System built on **Laravel 12 (PHP 8.5)** and a **Vue 3 SPA** (Vite, TypeScript, Tailwind CSS, shadcn-vue). This foundation phase delivers a production-grade skeleton: a versioned REST API with standardized envelopes and error handling, a Sanctum token authentication + RBAC layer (Spatie Laravel Permission) with 14 system roles, a layered backend (Repository / Service / FormRequest / Resource), and a Vue Router + Pinia + Axios + TanStack Query frontend with route guards, Zod-validated forms, and the KONEXUS brand design system.

No business modules (academics, finance, registrar, etc.) are implemented yet by design; the foundation exposes a clean extension path for them.

### Verification status
- Backend: 17 routes; all auth, session, role, profile, password, and error-handling endpoints pass smoke tests (200/401/404/422/429 all correct).
- Frontend: `vue-tsc --noEmit` passes, ESLint passes, `vite build` succeeds, SPA shell served at `/` with the production bundle.
- Database: 9 migrations + seeders applied on SQLite (local dev), 14 roles seeded, super admin provisioned. Migrations are portable to MySQL 8 for production.

---

## 2. System Architecture

```
┌─────────────────────────────┐        ┌─────────────────────────────┐
│  Browser (Vue 3 SPA)        │  HTTP  │  Laravel 12 REST API        │
│  Vue Router + Pinia         │ ─────▶ │  api/v1/* (token auth)      │
│  Axios (bearer token)       │        │  Controllers → Services     │
│  TanStack Vue Query         │ ◀───── │  → Repositories → Eloquent   │
└──────────────┬──────────────┘        └──────────────┬──────────────┘
               │                                      │
               │  Dev: Vite 5173 proxy → 8000         │  SQLite (dev) / MySQL 8 (prod)
               │  Prod: Laravel serves SPA shell      │
               └──────────────────────────────────────┘
```

- **Transport:** The SPA is served by Laravel (`routes/web.php` catch-all → `view('app')`). In development, Vite dev server (5173, HTTPS via EnvKit certs) proxies `/api` → `127.0.0.1:8000`.
- **Auth transport:** Sanctum **personal access tokens** (Bearer). Tokens are stored in `localStorage` (`konexus_token`) and attached by an Axios request interceptor.
- **Data flow:** Form → Zod schema → Pinia action → Axios → controller → `FormRequest` validation → service → repository → model. Responses return through resources inside a uniform envelope.

---

## 3. Technology Stack & Rationale

| Layer | Choice | Rationale / alternatives considered |
|---|---|---|
| Backend framework | Laravel 12 (PHP ^8.4, env 8.5.9) | Rich ecosystem, Eloquent ORM, migrations, built-in validation, rate limiting, queues, mail. |
| API token auth | laravel/sanctum ^4.0 | First-party Laravel; simple bearer tokens for SPA/mobile; `last_used_at`/expiry supported. Alternative: Passport (heavier, OAuth). |
| RBAC | spatie/laravel-permission ^6.0 | De-facto standard; roles + permissions with cached lookup. Alternative: hand-rolled ACL. |
| Auditing | spatie/laravel-activitylog ^4.9 | Opt-in activity trail per model. |
| Reporting (future) | barryvdh/laravel-dompdf ^3.0, maatwebsite/excel ^3.1 | PDF/invoice + spreadsheet export for report modules. |
| Frontend framework | Vue 3 (Composition API, `<script setup>`) | Matches starter; reactive, typed with vue-tsc. |
| SPA routing | vue-router ^4 | Client-side routing with route-level layouts and guards. (Replaced Inertia.) |
| State | pinia ^3 | Stores (auth) with typed state/computed. |
| HTTP | axios ^1 | Interceptors for token + error normalization. |
| Server state | @tanstack/vue-query ^5 | Caching/dedup for future module data fetching. |
| Validation | zod ^3 | Runtime schema + TS inference for forms. |
| Notifications | vue-sonner | Toast UX. |
| UI kit | shadcn-vue (radix-vue) + Tailwind CSS 3 | Accessible primitives; reuses EnvKit components; brand themed. |
| Linting/format | ESLint 9 + Prettier | PSR-12 on PHP via Laravel Pint. |

---

## 4. Code Organization

### Backend (`app/`)
```
app/
├── Enums/RoleEnum.php              # 14 system roles: name, label, description, seed data
├── Exceptions/ApiException.php     # typed HTTP exceptions (badRequest … serverError)
├── Support/ApiResponse.php         # {success, message, data, errors} envelope + fromThrowable
├── Traits/ApiResponseTrait.php     # controller helpers: success/error/paginated/collection
├── Helpers/helpers.php             # api_success, api_error, is_api_request, frontend_url
├── Repositories/
│   ├── Contracts/ (RepositoryInterface, UserRepositoryInterface)
│   ├── BaseRepository.php, UserRepository.php
├── Services/AuthService.php        # login, register, sessions, password, profile, account
├── Providers/                      # Repository, RateLimit, App (policies + gates)
├── Http/
│   ├── Controllers/Api/V1/         # ApiController + Auth/{Auth,Password,Session} + Role
│   ├── Middleware/ (Authenticate, EnsureUserHasRole)
│   ├── Requests/Auth/              # Login, Register, Forgot, Reset, UpdateProfile, ChangePassword
│   └── Resources/                  # UserResource, RoleResource, PermissionResource, AuthResource
├── Models/ (User, Role, Permission)
├── Notifications/ResetPasswordNotification.php
└── Policies/UserPolicy.php
```

### Frontend (`resources/js/`)
```
resources/js/
├── app.ts, App.vue, env.d.ts
├── router/index.ts                 # routes, auth guards, title sync, modules extension
├── modules/index.ts                # module route registration point
├── stores/auth.ts                  # Pinia auth store (token, user, sessions, account)
├── lib/api.ts                      # Axios instance, interceptors, error extractors
├── schemas/auth.ts                 # Zod schemas
├── constants/app.ts                # brand, route constants
├── types/index.ts                  # API + domain types
├── composables/useAppearance.ts    # theme system
├── layouts/                        # AppLayout, AuthLayout, settings/Layout, auth/*
├── pages/                          # Dashboard, auth/*, settings/*, errors/*
└── components/                     # AppShell, AppHeader, AppSidebar, Nav*, ui/*
```

---

## 5. Backend Architecture

### Request lifecycle
`Route (api/v1, throttle, auth:sanctum)` → `Controller` → `FormRequest` (validated) → `Service` (business rules) → `Repository` (persistence) → Model → `Resource` → `ApiResponse` envelope.

- **Controllers** are thin: parse input, call one service method, return the response trait helper.
- **Services** encapsulate business rules (e.g., `attemptLogin` checks `is_active`, stamps `last_login_at`, issues a token). Services depend on contracts (`UserRepositoryInterface`) via constructor injection.
- **Repositories** wrap Eloquent queries; bound in `RepositoryServiceProvider`.
- **FormRequests** carry validation + authorization concern where needed; errors surface as `422 { errors: { field: [messages] } }`.
- **ApiResponse / ApiException** give every endpoint the same JSON shape and map any thrown exception (including framework 401/404/429/500) through `bootstrap/app.php` exception rendering.

### Middleware & bootstrap (`bootstrap/app.php`)
- `api` routes under prefix `api`, JSON rendering for `api/*` or `expectsJson`.
- Aliases: `auth` (custom `Authenticate` returning 401 JSON for API, web redirect for browsers), `roles`, `role`, `permission`, `role_or_permission`.
- Providers registered: `RepositoryServiceProvider`, `RateLimitServiceProvider` (login 5/min, register 3/min, password 5/min).

---

## 6. API Design

### Conventions
- Base path `/api/v1`, route names `api.v1.*`.
- Versioning by URL prefix (`v1`) to evolve contracts without breaking clients.
- Uniform envelope:

```json
{ "success": true, "message": "…", "data": { … }, "errors": null }
```

- Errors use matching HTTP status; validation failures populate `errors` as `{ field: string[] }`.

### Endpoints (verified)
| Method | URI | Auth | Purpose |
|---|---|---|---|
| POST | `/api/v1/auth/login` | public (throttle:login) | Authenticate + issue bearer token |
| POST | `/api/v1/auth/register` | public (throttle:register) | Create account + token |
| POST | `/api/v1/auth/forgot-password` | public (throttle:password) | Send reset link |
| POST | `/api/v1/auth/reset-password` | public (throttle:password) | Reset password |
| GET | `/api/v1/auth/me` | sanctum | Current user + roles |
| PATCH | `/api/v1/auth/me` | sanctum | Update profile |
| DELETE | `/api/v1/auth/me` | sanctum | Delete account |
| PUT | `/api/v1/auth/password` | sanctum | Change password |
| POST | `/api/v1/auth/logout` | sanctum | Revoke current token |
| GET | `/api/v1/auth/sessions` | sanctum | List sessions |
| DELETE | `/api/v1/auth/sessions` | sanctum | Revoke all but current |
| DELETE | `/api/v1/auth/sessions/{token}` | sanctum | Revoke one session |
| GET | `/api/v1/roles` | sanctum | Roles index (RBAC) |
| GET | `/api/v1/roles/catalog` | public | Lightweight role picker data |

---

## 7. Data Model & Database Design

Database: SQLite in local development (zero-setup, portable). Migrations target MySQL 8 for production (utf8mb4). Migrations run cleanly via `php artisan migrate --seed`.

### Tables
- `users` — extended with `avatar`, `is_active`, `last_login_at`; Sanctum tokens, activity log morphs.
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (Spatie) — `roles` extended with `label`, `description`.
- `personal_access_tokens` (Sanctum, `konexus_` token prefix).
- `activity_log` (+ event/batch columns) — audit trail.
- `password_reset_tokens`, `sessions`, `cache`, `jobs`.

### Roles (RoleEnum)
`super-administrator`, `school-administrator`, `principal`, `registrar`, `finance-officer`, `teacher`, `adviser`, `guidance-counselor`, `school-nurse`, `librarian`, `hr-officer`, `inventory-officer`, `student`, `parent` — each with a human `label` and `description` surfaced in the catalog API.

### Seeding
`RolesAndPermissionsSeeder` creates the 14 roles and a super admin (`admin@konexus.local` / `password`, overridable via `SUPER_ADMIN_*` env). Idempotent via `firstOrCreate`.

---

## 8. Frontend Architecture

- **Bootstrap:** `app.ts` mounts the root `App.vue` (`<RouterView/>` + `<Toaster/>`) with Pinia, Vue Router, and TanStack Vue Query; theme initialized before mount to avoid flash.
- **Routing:** Route-level layouts. `/` → `AppLayout` (sidebar shell) → nested `Dashboard` + `/settings/*` (SettingsLayout) + `modules/*`. `/auth` → `AuthLayout` (centered card). Error routes `/403|404|500`. Global `beforeEach` guard: `initialize()` the auth store (fetch `/auth/me` when a token exists), enforce `requiresAuth`/`requiresGuest`, and forward the intended `redirect` query. Titles are synced in `afterEach`.
- **Auth store (Pinia):** holds `user`, `token`, `status`, `initialized`; actions for login/register/logout, sessions, profile, password, account deletion, role catalog. Registers the global 401 handler that clears credentials (so interceptors and guards stay consistent).
- **HTTP layer:** Axios instance (`baseURL /api/v1`), request interceptor injects `Authorization: Bearer <token>`, response interceptor fires the unauthorized handler on 401; `extractError`/`extractFieldErrors` normalize errors for toasts and inline field messages.
- **Forms:** Zod schemas produce both runtime validation and TS types; server field errors map back into `InputError` components.
- **Theme:** CSS variables for light/dark with the KONEXUS palette (Primary `#8B5E3C`, Secondary `#F5F2EC`, Accent `#D4A373`, Dark `#1F1F1F`, Success/Warning/Danger), Inter font, 16px radius. Theme preference persisted in `localStorage` and honors the OS scheme.

---

## 9. Security & Compliance

- **Authentication:** Sanctum bearer tokens with configurable expiry (`SANCTUM_TOKEN_EXPIRATION`, default 7 days) and `konexus_` prefix; per-user session revocation and "revoke all except current".
- **Rate limiting:** login (5/min), register (3/min), password (5/min) via dedicated `RateLimiter::for` definitions; verified to return `429`.
- **Authorization:** `auth:sanctum` on protected routes; Spatie middleware aliases (`roles`, `permission`, `role_or_permission`) ready for module enforcement; Gates (`manage-users`, `manage-roles`, `manage-settings`, `view-module`) and `UserPolicy` registered.
- **Input validation:** every write endpoint is guarded by a FormRequest; `current_password` rule confirms password changes.
- **Error discipline:** API paths never leak HTML/redirects; unauthenticated API calls return structured `401`, unknown API paths return `404` JSON (SPA catch-all excludes `api/*`).
- **Deactivated users** cannot log in (`is_active` gate in service).
- **CORS/CSRF:** bearer-token auth avoids CSRF concerns for API; SPA served same-origin in production; dev uses a Vite proxy. Laravel's `config/cors.php` remains available if a standalone frontend host is introduced.
- **Secrets:** credentials come from `.env`; super-admin seed reads env with safe defaults; no secrets committed.

---

## 10. Verification Results & Roadmap

### Verification matrix
| Check | Result |
|---|---|
| `composer` install + Pint (PSR-12) | Pass |
| `php artisan migrate --seed` (fresh SQLite) | Pass (14 roles + super admin) |
| `php artisan route:list` | 17 routes, correct names/middleware |
| Login → token → `/auth/me` | 200 with role label/description |
| Sessions list / revoke-all | 200 |
| Roles index (14) / public catalog | 200 |
| Profile update / password change / delete | 200/422 |
| Unauthenticated `/auth/me` | 401 JSON |
| Unknown API path | 404 JSON |
| Validation errors | 422 with field errors |
| Login rate limit | 429 |
| `vue-tsc --noEmit` / ESLint / `vite build` | Pass / Pass / Pass |
| SPA shell at `/` (production bundle) | 200 with `#app` + build assets |

### Roadmap (foundation → product)
1. **Permissions catalog** — define permissions per module and bind to roles; enforce with `permission` middleware + Gates.
2. **Modules** — register route groups under the authenticated shell via `modules/index.ts` (academics, registrar, finance, guidance, clinic, library, HR, inventory, reports).
3. **Data model** — school year / term setup, student & faculty profiles, sections, subjects, enrollments.
4. **Reporting** — DomPDF invoices/certificates and Excel exports (packages already installed).
5. **Hardening** — email verification, 2FA, audit review UI, activity-log retention.
6. **Deployment** — same-origin build, queue worker for mail/exports, TLS termination.
