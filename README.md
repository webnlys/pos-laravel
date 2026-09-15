# Simple POS

Laravel 11 + Vue 3 SPA point-of-sale. The Vue app talks to Laravel over REST (`/api`) with Sanctum cookie auth. Database is PostgreSQL.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# Set DB_* in .env (PostgreSQL / Supabase)
php artisan migrate --seed
php artisan storage:link
npm install
npm run dev
php artisan serve
```

Open http://127.0.0.1:8000

- Admin: `admin@simplepos.test` / `password`
- Customer: `customer@simplepos.test` / `password`

Production frontend: `npm run build`.

### Supabase / IPv6

Newer Supabase database hosts are IPv6-only. If PHP reports `Unknown host` or a connection timeout, use the **session pooler** (IPv4) from the Supabase dashboard:

- Host like `aws-0-<region>.pooler.supabase.com`
- Port `5432`
- Username `postgres.<project-ref>`
- `DB_SSLMODE=require`

Never commit `.env`.
