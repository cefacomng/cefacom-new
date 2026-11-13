# Cefacom

One platform to reach, notify and convert customers. A Nigerian-first SMS and Email messaging platform with one unified Naira wallet and pay-as-you-go billing.

**Status:** Batch 2 (authentication, roles and permissions). See `BUILD_STATUS.md`. No auth, wallet, providers, payments or sending exist yet.

## Stack
Laravel 13 (PHP 8.3+), React 19 + TypeScript + Inertia 3, Tailwind CSS 4, Vite, MySQL (production target 8.4 LTS), Redis + Laravel Horizon.

## Requirements
PHP 8.3+ with `pdo_mysql`, `redis`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `intl`; Composer 2; Node 22+; MySQL 8.x; Redis 7+.

## Local setup
```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate

# Create two MySQL databases and a user (names are examples; tests REQUIRE a database ending in _test):
#   CREATE DATABASE cefacom;      CREATE DATABASE cefacom_test;
#   CREATE USER 'cefacom'@'localhost' IDENTIFIED BY '<your password>';
#   GRANT ALL ON cefacom.* TO 'cefacom'@'localhost';  GRANT ALL ON cefacom_test.* TO 'cefacom'@'localhost';
# Then set DB_USERNAME / DB_PASSWORD in .env.

php artisan migrate
php artisan cefacom:sync-permissions   # creates roles and permissions; run after every migrate/deploy
php artisan cefacom:make-super-admin you@example.com   # first staff account (hidden password prompt)
php artisan cefacom:check     # verifies MySQL, Redis, queue, permissions, Horizon
npm run build
php artisan serve             # http://127.0.0.1:8000
php artisan horizon           # queue workers (separate terminal)
```

## Commands
| Task | Command |
|---|---|
| PHP tests (real MySQL + Redis) | `php artisan test` |
| Frontend component tests | `npm test` |
| TypeScript check | `npm run typecheck` |
| Production build | `npm run build` |
| Infrastructure check | `php artisan cefacom:check` |
| Frontend dev server | `npm run dev` |

Never commit `.env`, API keys, database dumps or provider credentials.

## Using the app locally
Open `/register` to create a customer (verification emails go to `storage/logs/laravel.log` while `MAIL_MAILER=log`). Sign in at `/login`: customers land in `/app`, staff in `/admin`. `/design-system` exists only in local development. See `docs/verification/` for the browser checks.
