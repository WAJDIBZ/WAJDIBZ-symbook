# SymBook (BookStore) — Symfony 7.2

Online bookstore demo built with **Symfony 7.2**, **PostgreSQL**, **Mercure**, and a simple cart/checkout flow.

## Features

- Browse books + categories
- Cart (panier) + orders
- Admin area (books, categories, users, orders)
- Email features (verification / reset password)
- Optional Gmail OAuth2 mailer support
- Stripe checkout (test mode)

## Tech Stack

- PHP >= 8.2
- Symfony 7.2
- PostgreSQL (Docker)
- Mercure hub (Docker)
- Mailpit (Docker) for local emails

## Quick Start (recommended: Docker for services)

### 1) Start services (Postgres + Mercure + Mailpit)

```bash
docker compose up -d
```

- Postgres: `localhost:5432`
- Mailpit UI: http://localhost:8025
- Mailpit SMTP: `localhost:1025`
- Mercure: http://localhost/.well-known/mercure

### No Docker? Use SQLite (fastest local demo)

If you don't have Docker installed, you can run the app with SQLite:

1. Create `.env.local`:

```bash
copy .env.local.example .env.local
```

2. Edit `.env.local` and set:

```env
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

3. Run migrations/fixtures as usual.

### 2) Install PHP dependencies

```bash
composer install
```

### 3) Configure environment

Create a local env file:

```bash
copy .env.local.example .env.local
```

Then edit `.env.local` (DB, Stripe keys, etc.).

Note: `APP_SECRET` must be non-empty. If you see "A non-empty secret is required", set a value in `.env.local`.

### 4) Database setup

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
```

If migrations fail on a brand-new database (some migrations start with `ALTER TABLE ...`), use this fallback:

```bash
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load -n
```

### 5) Run the app

Option A (Symfony CLI):

```bash
symfony serve
```

Option B (built-in PHP server):

```bash
php -S 127.0.0.1:8000 -t public
```

Then open: http://127.0.0.1:8000

## Email

Default local mailer uses Mailpit:

- `MAILER_DSN=smtp://localhost:1025`

OAuth2 email instructions: see [OAUTH_README.md](OAUTH_README.md)

## Stripe

Stripe checkout requires a **test** secret key:

- Set `STRIPE_SECRET_KEY` in `.env.local`

Example:

```env
STRIPE_SECRET_KEY=sk_test_...
```

## Repo name on GitHub

A good GitHub repo name for this project would be:

- `symbook` or `symbook-symfony`

You can rename the repo from GitHub Settings → Repository name.

## Security note

If you previously committed any API keys/tokens to GitHub, rotate them (Stripe keys, OAuth tokens, app secrets) and avoid committing secrets going forward. Use `.env.local` (gitignored) or Symfony secrets.
