# Opaska - Development Guidelines

## Project Overview

Opaska is a self-hosted comment system built with Laravel, designed to replace isso.

## Tech Stack

- **Backend**: Laravel 12, PHP 8.3+, Laravel Actions
- **Database**: SQLite (default) or PostgreSQL
- **Admin Panel**: React + TypeScript + Inertia.js v2 + Mantine UI v8
- **Embed Widget**: TypeScript + Preact + Mantine (via preact/compat), <25KB
- **Deployment**: Docker with FrankenPHP

## Architecture Patterns

### Laravel Actions
Use `lorisleiva/laravel-actions` for all business logic:
```php
class CreateComment
{
    use AsAction;

    public function handle(Thread $thread, array $data): Comment
    {
        // Business logic here
    }

    public function asController(Request $request, string $uri): JsonResponse
    {
        // HTTP handling here
    }
}
```

### Database Driver Pattern
For features that differ between SQLite and PostgreSQL (like full-text search), use the driver pattern:
```php
$driver = match (DB::connection()->getDriverName()) {
    'pgsql' => new PostgresSearchDriver,
    default => new SqliteSearchDriver,
};
```

### Single Tenant
This is a single-tenant application. No multi-site support, one admin user only.

## Code Style

### PHP
- Use `declare(strict_types=1);` in all PHP files
- Use constructor property promotion
- Use typed properties and return types
- Follow PSR-12 coding style (enforced by Pint)
- Use PHPDoc for complex types

### TypeScript/React (Admin Panel)
- Use functional components with hooks
- Use Mantine components for UI
- Use Inertia's `useForm` for form handling
- Use Inertia's `Link` component for navigation
- Linting enforced by Biome

## Testing

Run tests for both database drivers:
```bash
# SQLite (default)
php artisan test

# PostgreSQL
php artisan test --configuration=phpunit.pgsql.xml
```

## Key Directories

- `app/Actions/` - Business logic (Laravel Actions)
- `app/Models/` - Eloquent models
- `app/Search/` - Database-specific search drivers
- `embed/` - Embed widget source code (separate build)
- `resources/js/` - Admin panel React components

## Frontend Builds

### Admin Panel (Inertia + Mantine)
Located in `resources/js/`
Build: `npm run build`
- Full React app with Mantine UI
- Size not critical (admin only)

### Embed Widget (Preact + Mantine)
Located in `embed/`
Build: `cd embed && npm run build`
Output: `public/embed.js`
- Target size: **<25KB gzipped**
- Uses Preact with `preact/compat` for Mantine compatibility
- Separate Vite config optimized for size

## Database

Default: SQLite at `database/database.sqlite`
Switch to PostgreSQL via `DB_CONNECTION=pgsql` in `.env`

## Security Considerations

- All comment content is sanitized
- IPs are anonymized (/24 IPv4, /48 IPv6)
- CORS validation for embed requests
- CSRF protection for admin actions
- Rate limiting on comment creation
