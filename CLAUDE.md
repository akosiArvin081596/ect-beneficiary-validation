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

## Architecture Overview

This is a Laravel 12 + Inertia v2 + Vue 3 SPA using the Laravel Vue starter kit.

### Authentication
Laravel Fortify handles all auth flows (login, register, password reset, email verification, 2FA). Views are rendered as Inertia pages via `FortifyServiceProvider` — do not add Blade auth routes. Fortify actions live in `app/Actions/Fortify/`.

### Routing
- `routes/web.php` — public and authenticated routes
- `routes/settings.php` — user settings routes (included from web.php)
- Middleware configured in `bootstrap/app.php` (not Kernel.php)
- `HandleInertiaRequests` shares `auth.user`, `name`, and `sidebarOpen` as global Inertia props

### Frontend Structure
- **Pages**: `resources/js/pages/` — Inertia page components
- **Layouts**: `resources/js/layouts/` — `AppLayout.vue` (wraps sidebar layout), `AuthLayout.vue` (wraps auth layouts)
- **Components**: `resources/js/components/` — app-level components; `resources/js/components/ui/` — shadcn-style primitives built on reka-ui
- **Types**: `resources/js/types/` — TypeScript types; `global.d.ts` extends Inertia shared props
- **Routes**: `@/routes` — Wayfinder-generated typed route helpers; `@/actions` — Wayfinder-generated typed action helpers

### Wayfinder (Type-Safe Routes)
Import route helpers from `@/routes` or controller actions from `@/actions` in Vue components — never hardcode URLs. See `AppSidebar.vue` and `Dashboard.vue` for usage examples.

### UI Components
The project uses reka-ui primitives with shadcn-style wrappers. Always check `resources/js/components/ui/` for existing components before creating new ones. Icons use `lucide-vue-next`.

### Shared Inertia Props (global.d.ts)
```ts
{ name: string; auth: Auth; sidebarOpen: boolean }
```

### Testing Setup
- Feature tests use `RefreshDatabase` (configured in `tests/Pest.php`)
- Tests mirror the app structure: `tests/Feature/Auth/`, `tests/Feature/Settings/`
- Create tests with `php artisan make:test --pest {Name}`
