# affiliateMY

An affiliate marketplace connecting **brands** with **creators**. Brands list products for promotion; creators apply, get approved, and promote products through their connected social accounts (starting with YouTube).

## Tech stack

- **Backend**: Laravel 13 (PHP 8.3), MySQL, Laravel Sanctum (API token auth)
- **Frontend**: Nuxt 4 (Vue 3), Pinia, Tailwind CSS, TanStack Table
- **Auth**: Sanctum-based API tokens, role middleware (`brand`, `creator`, `admin`)

## Project structure

```
.
├── app/                # Laravel application code (Controllers, Models, Services, Repositories)
├── database/           # Migrations, factories, seeders
├── routes/api.php       # API routes consumed by the frontend
├── frontend/            # Nuxt 4 application (separate npm project)
└── resources/           # Laravel-served assets (Blade welcome page only)
```

The backend is API-only (`routes/api.php`); the Nuxt app in `frontend/` is a separate project with its own `package.json` and talks to the API over HTTP.

## Features

- **Roles**: `brand`, `creator`, `admin` (see `App\Enums\UserRole`)
- **Brands**: create/manage products & variations, review creator applications, view creator portfolios
- **Creators**: browse the marketplace, apply to promote products, connect & verify a YouTube account, track applications and earnings
- **Applications**: pending → approved/rejected workflow (see `App\Enums\ApplicationStatus`)
- **Notifications**: in-app notifications for application status changes
- **Google/YouTube integration**: creators connect a YouTube channel for verification via `App\Services\YoutubeService`

## Prerequisites

- PHP 8.3+
- Composer
- Node.js + npm
- MySQL
- [Laravel Herd](https://herd.laravel.com) (or any local PHP dev environment)

## Backend setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env` (defaults to a local MySQL database named `affiliatemy`), then run:

```bash
php artisan migrate
```

Set the following in `.env` for YouTube verification to work:

```
YOUTUBE_API_KEY=
```

Serve the API (if not using Herd):

```bash
php artisan serve
```

## Frontend setup

```bash
cd frontend
npm install
npm run dev
```

The frontend expects the API at `http://affiliatemy.test/api` by default (see `frontend/nuxt.config.ts` `runtimeConfig.public`). Update `apiBase`, `storageBase`, and `googleClientId` there if your local setup differs.

## Testing

```bash
php artisan test
```

## API overview

All API routes are under `/api` (see `routes/api.php`):

- `POST /api/auth/register`, `login`, `forgot-password`, `reset-password` — public
- `GET /api/auth/me`, `POST /api/auth/logout` — authenticated
- `/api/brand/*` — brand-only (products, variations, applications, creator portfolios)
- `/api/creator/*` — creator-only (marketplace, applications, social account connection)
- `/api/notifications/*` — shared between roles
