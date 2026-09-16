# Simple POS

Laravel 11 + Vue 3 SPA point-of-sale. The Vue app talks to Laravel over REST (`/api`) with Sanctum cookie auth. Database is MySQL (Laragon).

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# Set DB_* in .env for local MySQL (Laragon defaults: root / empty password)
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

### Local MySQL (Laragon)

Create the database once, then migrate:

```sql
CREATE DATABASE simple_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`.env` example:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simple_pos
DB_USERNAME=root
DB_PASSWORD=
```

Never commit `.env`.
