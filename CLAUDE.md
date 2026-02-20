# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Development
```bash
composer run dev          # Start all dev servers (Laravel + Vite + queue)
npm run dev               # Vite only
npm run build             # Production asset build
```

### Testing
```bash
php artisan test --compact                        # Run all tests
php artisan test --compact --filter=TestName      # Run specific test
php artisan test --compact tests/Feature/Auth/    # Run a directory of tests
```

### Code Quality
```bash
vendor/bin/pint --dirty --format agent   # Format PHP (run before finalizing changes)
npm run lint                              # ESLint fix
npm run format                            # Prettier format
```

### Artisan
```bash
php artisan admin:create                  # Create admin user interactively
```

## Architecture Overview

This is a **DSWD Caraga ECT Beneficiaries Validation** system — a Laravel 12 + Inertia v2 + Vue 3 SPA for collecting and validating emergency cash transfer beneficiary data. It features offline-first data entry with automatic sync.

### Authentication
Laravel Fortify handles all auth flows (login, register, password reset, email verification, 2FA). Views are rendered as Inertia pages via `FortifyServiceProvider` — do not add Blade auth routes. Fortify actions live in `app/Actions/Fortify/`. Password rules require min 12 chars, mixed case, numbers, symbols, uncompromised (in production).

### Routing
- `routes/web.php` — public and authenticated routes
- `routes/settings.php` — user settings routes (included from web.php)
- Middleware configured in `bootstrap/app.php` (not Kernel.php)
- `HandleInertiaRequests` shares `auth.user`, `name`, and `sidebarOpen` as global Inertia props
- `HandleAppearance` shares appearance preference (light/dark/system) from cookie

### Frontend Structure
- **Pages**: `resources/js/pages/` — Inertia page components
- **Layouts**: `resources/js/layouts/` — `AppLayout.vue` (wraps sidebar layout), `AuthLayout.vue` (wraps auth layouts), `SettingsLayout.vue` (settings sidebar)
- **Components**: `resources/js/components/` — app-level components; `resources/js/components/ui/` — shadcn-style primitives built on reka-ui
- **Types**: `resources/js/types/` — TypeScript types; `global.d.ts` extends Inertia shared props
- **Routes**: `@/routes` — Wayfinder-generated typed route helpers; `@/actions` — Wayfinder-generated typed action helpers

### Wayfinder (Type-Safe Routes)
Import route helpers from `@/routes` or controller actions from `@/actions` in Vue components — never hardcode URLs. See `AppSidebar.vue` and `Dashboard.vue` for usage examples.

### UI Components
The project uses reka-ui primitives with shadcn-style wrappers (new-york-v4 style). Always check `resources/js/components/ui/` for existing components before creating new ones. Icons use `lucide-vue-next`.

### Shared Inertia Props (global.d.ts)
```ts
{ name: string; auth: Auth; sidebarOpen: boolean }
```

### Domain Model

The core domain is beneficiary data collection for emergency cash transfers:

- **Beneficiary** — central entity with personal info, location, damage classification, NHTS-PR rating, applicable sectors (JSON array), civil status, and boolean flags for family cohabitation
- **BeneficiaryChild**, **BeneficiarySibling**, **BeneficiaryRelative** — HasMany relations from Beneficiary, each tracking name + birth_date (relatives also have `relationship`)
- **Province → Municipality → Barangay** — hierarchical location models with PSGC codes, seeded for Caraga region via `PsgcCaragaSeeder`

Conditional validation: family member details (father, mother, spouse, children, siblings, relatives) are only required when their corresponding `living_with_*` boolean is true. See `StoreBeneficiaryRequest` for the full validation ruleset.

### Offline-First Architecture

The app supports offline data entry with sync-when-online:

- **`useOfflineQueue` composable** — queues beneficiary submissions in localStorage, syncs to `/beneficiaries/offline-sync` endpoint when online, handles 422 (validation), 401/419 (session expired), and 5xx errors with retry logic
- **`useDraft` composable** — persists form state in localStorage for recovery on page reload
- **`BeneficiaryController@offlineSync`** — dedicated endpoint for syncing offline entries (returns JSON 201)
- Auto-syncs on `online` event and on app load

### Beneficiary Creation Flow

`BeneficiaryController@store` and `offlineSync` both use `createBeneficiaryWithRelations()` which wraps creation in a DB transaction — creates the beneficiary then bulk-creates siblings, children, and relatives from array inputs.

### Key Composables
- `useOfflineQueue` — offline queue management with sync
- `useDraft` — form draft persistence/recovery
- `useAppearance` — theme management (light/dark/system)
- `useTwoFactorAuth` — 2FA setup flow (QR code, manual key, recovery codes)
- `useCurrentUrl` — reactive URL matching for navigation
- `useInitials` — extracts initials from full name

### Testing Setup
- Feature tests use `RefreshDatabase` (configured in `tests/Pest.php`)
- Tests mirror the app structure: `tests/Feature/Auth/`, `tests/Feature/Settings/`
- Create tests with `php artisan make:test --pest {Name}`
- Factories: `BeneficiaryFactory` supports `->withSiblings(n)`, `->withChildren(n)`, `->withRelatives(n)`, `->withAllFamily()` chaining
