# Local Setup (CareCRM)

## Requirements
- PHP 8.1 - 8.3 (8.4 is not supported by current framework stack)
- Composer
- MySQL
- Node.js & npm (optional, for assets)

## Steps

### 1. Install dependencies
```bash
composer install
```

### 2. Environment
`.env` file already configured for local. Adjust if needed:
- `DB_DATABASE` = your local database name (default: ivr)
- `DB_USERNAME` = root
- `DB_PASSWORD` = (empty or your password)

### 3. Generate key & storage link
```bash
php artisan key:generate
php artisan storage:link
```

### 4. Database
```bash
php artisan migrate
```

### 5. Run locally
```bash
php artisan serve
```
App: **http://127.0.0.1:8000**

### 6. Run Reverb (required for realtime chat)
```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

If Reverb is not running, chat messages are still saved but realtime updates will not be pushed to connected clients.

## Production Notes (Reverb)
- Run Reverb as a long-running process using `systemd` or `supervisor`.
- Use non-localhost host values in production env:
  - `REVERB_HOST=<public-or-private-host>`
  - `REVERB_PORT=8080` (or your mapped port)
  - `REVERB_SCHEME=http|https` as per TLS setup
- After env/config changes run:
```bash
php artisan config:clear
php artisan cache:clear
```
