# Self-Hosting Bulla

This guide covers deploying Bulla on your own server.

## Requirements

- Docker and Docker Compose (recommended)
- OR: PHP 8.3+, Composer, Node.js 20+

## Quick Start with Docker

### SQLite (Simplest)

```bash
docker run -d \
  -p 8000:8000 \
  -v bulla_data:/app/database \
  -e APP_URL=https://comments.example.com \
  ghcr.io/your-username/bulla:latest
```

### With PostgreSQL

```yaml
# docker-compose.yml
services:
  bulla:
    image: ghcr.io/your-username/bulla:latest
    ports:
      - "8000:8000"
    environment:
      APP_URL: https://comments.example.com
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_DATABASE: bulla
      DB_USERNAME: bulla
      DB_PASSWORD: secret
    depends_on:
      - postgres

  postgres:
    image: postgres:16-alpine
    volumes:
      - postgres_data:/var/lib/postgresql/data
    environment:
      POSTGRES_DB: bulla
      POSTGRES_USER: bulla
      POSTGRES_PASSWORD: secret

volumes:
  postgres_data:
```

Run with:
```bash
docker compose up -d
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_URL` | Public URL of your Bulla instance | `http://localhost` |
| `APP_KEY` | Application encryption key (auto-generated if missing) | - |
| `DB_CONNECTION` | Database driver: `sqlite` or `pgsql` | `sqlite` |
| `DB_HOST` | PostgreSQL host | `localhost` |
| `DB_PORT` | PostgreSQL port | `5432` |
| `DB_DATABASE` | Database name | `bulla` |
| `DB_USERNAME` | Database username | - |
| `DB_PASSWORD` | Database password | - |
| `MAIL_MAILER` | Mail driver: `smtp`, `log` | `log` |
| `MAIL_HOST` | SMTP host | - |
| `MAIL_PORT` | SMTP port | `587` |
| `MAIL_USERNAME` | SMTP username | - |
| `MAIL_PASSWORD` | SMTP password | - |
| `MAIL_FROM_ADDRESS` | From email address | - |
| `IMGPROXY_URL` | [imgproxy](https://imgproxy.net) base URL | - |
| `IMGPROXY_KEY` | imgproxy signing key | - |
| `IMGPROXY_SALT` | imgproxy signing salt | - |

**Image Proxy (optional):** If all three `IMGPROXY_*` variables are set, avatars will be proxied through imgproxy for resizing and WebP conversion. If not configured, original avatar URLs are used.

## Initial Setup

1. Visit `https://your-domain.com/admin/setup`
2. Set your site name
3. Create admin credentials

## GitHub Login (Optional)

Allow commenters to authenticate with GitHub instead of entering name/email manually.

1. Go to [GitHub Developer Settings](https://github.com/settings/developers)
2. Click "New OAuth App"
3. Fill in:
   - **Application name:** Your site name (e.g., "My Blog Comments")
   - **Homepage URL:** Your site URL
   - **Authorization callback URL:** `https://your-bulla-url/auth/github/callback`
4. Copy the Client ID and generate a Client Secret
5. In Bulla Admin > Settings > Authentication:
   - Enable "GitHub Login"
   - Enter your Client ID and Client Secret

## Reverse Proxy Setup

### Caddy

```
comments.example.com {
    reverse_proxy localhost:8000
}
```

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name comments.example.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## Backups

### SQLite

The database is stored at `database/database.sqlite`. Back up this file regularly:

```bash
docker cp bulla:/app/database/database.sqlite ./backup-$(date +%F).sqlite
```

### PostgreSQL

```bash
docker compose exec postgres pg_dump -U bulla bulla > backup-$(date +%F).sql
```

## Updating

```bash
docker compose pull
docker compose up -d
```

## Manual Installation

If not using Docker:

```bash
# Clone repository
git clone https://github.com/your-username/bulla
cd bulla

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
npm install
npm run build

# Build embed widget
cd embed && npm install && npm run build && cd ..

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Serve with PHP's built-in server (development only)
php artisan serve

# Or use a proper web server like nginx + php-fpm
```
