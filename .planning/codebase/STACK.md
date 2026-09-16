# Technology Stack

**Analysis Date:** 2026-09-16

## Languages

**Primary:**
- PHP 8.4 - Backend application logic, Eloquent ORM, service layer
- JavaScript (Module) - Frontend assets bundling with Vite
- CSS - Styling via Tailwind CSS 4

**Runtime & Package Managers:**
- Composer 2.x - PHP package management
- npm/Node.js 22.x - JavaScript/frontend dependencies

## Runtime Environment

**Application Framework:**
- Laravel 13.8 - Web framework, routing, database abstraction, authentication
- Filament 5.6 - Admin panel UI and form builder
- Livewire 4.x - Reactive component framework (bundled with Filament)

**Runtime Client Libraries:**
- Predis 3.4 - PHP Redis client for cache, sessions, and queues

## Frameworks & Libraries

**Core Web:**
- Laravel Framework 13.8 - HTTP request handling, routing, middleware
- Filament 5.6 - Admin panel, resource management, form components
- Livewire 4.x - Reactive UI components (included with Filament)

**CLI & Development:**
- Laravel Tinker 3.0 - REPL for application context exploration
- Laravel Pint 1.27 - PHP code formatter and linter
- Laravel Pail 1.2.5 - Log viewer in terminal
- Laravel Boost 2.2 - MCP server for enhanced dev tooling
- Laravel PAO 1.0.6 - Build tool

**Testing:**
- Pest 4.7 - Test framework and runner
- Pest Laravel Plugin 4.1 - Pest integration with Laravel
- PHPUnit 12.x - Test infrastructure (used by Pest)
- Faker 1.23 - Test data generation
- Mockery 1.6 - Mocking library

**Frontend Build:**
- Vite 8.0 - JavaScript module bundler
- Lararel Vite Plugin 3.1 - Laravel integration for Vite
- Tailwind CSS 4.0 - Utility-first CSS framework
- @tailwindcss/vite 4.0 - Vite plugin for Tailwind

**Development:**
- concurrently 9.0.1 - Run multiple processes in parallel (dev command)
- Collision 8.6 - Pretty error reporting
- Composer Scripts - Post-install/update automation

## Key Dependencies

**Critical:**
- `filament/filament` 5.6 - Admin UI and CRUD operations
- `laravel/framework` 13.8 - Core web framework
- `predis/predis` 3.4 - Redis client (required for cache, sessions, queues)

**Database & ORM:**
- Laravel Eloquent (included in framework) - ORM with support for PostgreSQL, MySQL, SQLite, SQL Server

**Development & Build:**
- `laravel/boost` 2.2 - MCP server, enhanced CLI tools
- `pestphp/pest` 4.7 - Modern testing framework
- `laravel/pint` 1.27 - Code formatter and linter

## Configuration Files

**Environment:**
- `.env` / `.env.example` - Application configuration via environment variables
- `config/app.php` - Application name, debug, timezone (defaults to America/Bogota)
- `config/contpass.php` - Custom app configuration (single-tenant company NIT)

**Database:**
- `config/database.php` - Database connections (PostgreSQL default in .env)
- `config/redis.php` - Redis connection pooling and options

**Cache & Sessions:**
- `config/cache.php` - Cache store drivers (Redis default in .env)
- `config/session.php` - Session driver (Redis default in .env)

**Queues:**
- `config/queue.php` - Queue connections (Redis default in .env)

**Mail:**
- `config/mail.php` - Email drivers (Log driver default in development)

**Build:**
- `vite.config.js` - Vite bundler configuration with Tailwind and Laravel plugins
- `package.json` - npm scripts and dev dependencies
- `composer.json` - PHP dependencies and composer scripts
- `phpunit.xml` - Test environment configuration

**Development:**
- `.editorconfig` - Editor formatting standards
- `.npmrc` - npm configuration
- `boost.json` - Laravel Boost configuration

## Platform Requirements

**Development:**
- PHP 8.4+
- Composer 2.x
- Node.js 22.x (recommended)
- PostgreSQL 12+ (or MySQL 8+, SQLite for local development)
- Redis 6+ (for cache, sessions, and queues)
- Laravel Herd (for local development server)

**Development Environment Specifics:**
- Application runs on `https://contpass.test` via Laravel Herd
- Default timezone: America/Bogota
- Default locale: Spanish (es_CO)
- Default faker locale: Spanish Colombian (es_CO)

**Production Deployment:**
- PostgreSQL database (primary target)
- Redis for cache, sessions, and queue workers
- Compatible with Laravel Cloud deployment
- PHP CLI 8.4 for artisan commands

## Build Pipeline

**Frontend Build:**
- `npm run build` - Production Vite build (CSS, JS asset compilation)
- `npm run dev` - Development Vite server with HMR
- Fonts: IBM Plex Sans and Space Grotesk via Bunny (CDN-hosted)

**PHP Development:**
- `composer install` - Install dependencies
- `php artisan migrate` - Run database migrations
- `php artisan seed` - Populate initial data
- `vendor/bin/pint` - Format PHP code
- `php artisan test` - Run Pest test suite

**Concurrent Dev:**
- `composer run dev` - Runs PHP server, queue listener, log watcher, and Vite HMR in parallel

---

*Stack analysis: 2026-09-16*
