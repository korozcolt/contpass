# External Integrations

**Analysis Date:** 2026-09-16

## APIs & External Services

**Currently Active:**
- None detected in core application (isolated accounting system)

**Available but Unused:**
- AWS S3 - Configured in `config/filesystems.php` but not required
- AWS SQS - Queue driver option in `config/queue.php`
- AWS DynamoDB - Cache store option in `config/cache.php`

## Data Storage

**Databases:**

**Primary (PostgreSQL):**
- Driver: `pgsql`
- Default in `.env.example` and `config/database.php`
- Connection: `DB_HOST`, `DB_PORT=5432`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- SSL Mode: `DB_SSLMODE=prefer`
- ORM: Eloquent (Laravel's query builder and ORM)

**Alternative Options:**
- SQLite - `driver: sqlite` (used in testing: `:memory:`)
- MySQL 8+ - `driver: mysql`
- MariaDB 10.3+ - `driver: mariadb`
- SQL Server - `driver: sqlsrv`

**Cache Store:**
- **Primary:** Redis
  - Client: Predis 3.4
  - Connection: `REDIS_HOST`, `REDIS_PORT=6379`, `REDIS_DB=0`
  - Cache DB: `REDIS_CACHE_DB=1` (separate namespace)
  - Store key: `CACHE_STORE=redis`
  
- **Alternative Options:**
  - Database table (`cache` table)
  - Memcached (localhost:11211)
  - Array (in-memory, per-request)
  - File system
  - AWS DynamoDB
  - Laravel Octane

**File Storage:**
- **Local:** `storage/app` directory (default in `config/filesystems.php`)
- **Options:**
  - AWS S3 (`AWS_BUCKET`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`)

## Authentication & Identity

**Auth Provider:**
- Custom/Built-in Laravel + Filament
  - Implementation: Session-based web guard (Eloquent user provider)
  - User model: `App\Models\User`
  - Guard: `web` (session driver)
  - Password reset: Database token-based
  - Token table: `password_reset_tokens`
  - Expiry: 60 minutes

**Filament Authentication:**
- Filament v5.6 admin panel authentication
- Login route: `/admin/login`
- Protected resources require authenticated session
- Roles: `admin`, `accountant`, `viewer`

**Session Management:**
- Driver: Redis (default in `.env.example`)
- Lifetime: 120 minutes (configurable via `SESSION_LIFETIME`)
- Encryption: Optional (`SESSION_ENCRYPT=false` by default)
- Alternative: Array (testing), Database

## Monitoring & Observability

**Error Tracking:**
- None detected (no Sentry, Rollbar, or similar integration)

**Logs:**
- Default: Stack channel with single file rotation
- Channel: `stack`
- Stack drivers: `single` file
- Log level: `debug` (in development)
- Alternative: `daily` rotation, `syslog`, `errorlog`, `single`
- Storage: `storage/logs/laravel.log`

**Terminal Logs:**
- Laravel Pail 1.2.5 - Real-time log viewing in terminal via `php artisan pail`

**Development Error Reporting:**
- Collision 8.6 - Pretty error formatting in console and web

## CI/CD & Deployment

**Hosting:**
- Primary: Laravel Cloud (recommended deployment)
- Local Development: Laravel Herd
- Alternative: Any server with PHP 8.4, Composer, and Node.js

**Version Control:**
- Git (repository tracked via `.gitignore` and `.gitattributes`)

**CI Pipeline:**
- Not configured in this codebase
- Ready for: GitHub Actions, GitLab CI, or Jenkins

**Deployment Tools:**
- Laravel Boost CLI - Enhanced Artisan commands for build/deploy
- Composer scripts - Automated post-install/update steps
- Vite manifest - Built assets referenced via `@vite()` in Blade templates

## Environment Configuration

**Required Environment Variables:**

**Application:**
- `APP_NAME=ContPass`
- `APP_ENV=local|production`
- `APP_KEY=` - Laravel encryption key (generated via `php artisan key:generate`)
- `APP_URL=` - Application URL
- `APP_DEBUG=true|false`
- `APP_TIMEZONE=America/Bogota`
- `APP_LOCALE=es` / `APP_FALLBACK_LOCALE=es` / `APP_FAKER_LOCALE=es_CO`

**Database (PostgreSQL):**
- `DB_CONNECTION=pgsql`
- `DB_HOST=127.0.0.1` / `DB_PORT=5432`
- `DB_DATABASE=contpass`
- `DB_USERNAME=contpass`
- `DB_PASSWORD=` (empty in local development)
- `DB_SSLMODE=prefer`

**Cache & Sessions:**
- `CACHE_STORE=redis`
- `CACHE_PREFIX=contpass_cache`
- `SESSION_DRIVER=redis`
- `SESSION_CONNECTION=default`

**Redis:**
- `REDIS_CLIENT=predis`
- `REDIS_HOST=127.0.0.1`
- `REDIS_PORT=6379`
- `REDIS_PASSWORD=null` (no auth in local dev)
- `REDIS_DB=0` (default queue/session)
- `REDIS_CACHE_DB=1` (separate cache namespace)
- `REDIS_QUEUE_CONNECTION=default`

**Queue:**
- `QUEUE_CONNECTION=redis`
- `REDIS_QUEUE=default`

**Mail:**
- `MAIL_MAILER=log` (logs to file in development)
- `MAIL_HOST=127.0.0.1` / `MAIL_PORT=2525`
- `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME`
- Alternative drivers: `smtp`, `ses`, `postmark`, `resend`, `sendmail`

**Security:**
- `BCRYPT_ROUNDS=12` (password hashing)

**Secrets Location:**
- `.env` file (not committed to git, included in `.gitignore`)
- Production: Environment variables in deployment platform (Laravel Cloud, docker-compose, etc.)

## Webhooks & Callbacks

**Incoming:**
- None detected

**Outgoing:**
- None detected

## Broadcasting

**Current Configuration:**
- `BROADCAST_CONNECTION=log` (disabled/logged only)
- Channel: Not actively used in this MVP

## Testing Environment

**Test Database:**
- SQLite in-memory (`:memory:`) configured in `phpunit.xml`
- Isolated from production database

**Test Cache:**
- Array driver (in-memory per test) via `CACHE_STORE=array` in `phpunit.xml`

**Test Mail:**
- Array mailer via `MAIL_MAILER=array` in `phpunit.xml`

**Test Queue:**
- Sync driver via `QUEUE_CONNECTION=sync` in `phpunit.xml`

**Test Session:**
- Array driver via `SESSION_DRIVER=array` in `phpunit.xml`

## Custom Application Configuration

**Single-Tenant Setup:**
- `config/contpass.php`
- `CONTPASS_COMPANY_NIT` - Optional NIT of active company (if empty, uses first company record)

## Developer Tools & MCP

**Laravel Boost MCP Server:**
- Enabled via `.mcp.json`
- Command: `php artisan boost:mcp`
- Provides semantic documentation search, database schema inspection, and other CLI-connected tools

---

*Integration audit: 2026-09-16*
