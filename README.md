# Opaska

A self-hosted comment system for static sites and blogs. Modern replacement for isso.

## Features

- **Simple embedding** - Single script tag to add comments
- **Markdown support** - Comments support GitHub-flavored markdown
- **Nested replies** - Configurable nesting depth
- **Dark mode** - Light, dark, or auto (follows system preference)
- **Email notifications** - Notify authors when they receive replies
- **Email verification** - Verified badge for confirmed emails
- **Upvoting** - Anonymous upvotes with duplicate prevention
- **Moderation** - Approve, spam, delete from admin panel or email
- **Spam protection** - Honeypot, rate limiting, blocked words (no 3rd party)
- **Import from isso** - Migrate your existing comments
- **Atom feeds** - Subscribe to comment threads
- **Dual database support** - SQLite (default) or PostgreSQL
- **Lightweight embed** - ~9KB gzipped

## Quick Start

### Docker (Recommended)

```bash
docker run -d \
  -p 8000:8000 \
  -v opaska_data:/app/database \
  -e APP_URL=https://comments.example.com \
  ghcr.io/your-username/opaska:latest
```

Then visit `http://localhost:8000/admin/setup` to configure.

### Embedding

Add to your website:

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-opaska="https://comments.example.com"
  async
></script>
<div id="opaska-thread"></div>
```

## Documentation

- [Self-Hosting Guide](docs/self-hosting.md)
- [Embedding Guide](docs/embedding.md)
- [API Reference](docs/api.md)

## Tech Stack

- **Backend:** Laravel 12, PHP 8.3+
- **Admin Panel:** React, Inertia.js, Mantine
- **Embed Widget:** Preact, CSS
- **Database:** SQLite (default) or PostgreSQL
- **Deployment:** Docker with FrankenPHP

## Development

```bash
# Clone repository
git clone https://github.com/your-username/opaska
cd opaska

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run dev

# Build embed widget
cd embed && npm install && npm run build && cd ..

# Run tests
php artisan test

# Start development server
php artisan serve
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Feature/Comment

# With coverage
php artisan test --coverage
```

Tests run against both SQLite and PostgreSQL in CI.

## Migrating from isso

1. Export your isso database file
2. Go to Admin > Import
3. Upload the isso SQLite file
4. Comments will be imported with original timestamps

## Configuration

All configuration is done through the admin panel after initial setup. Key settings:

- **Moderation mode:** None, unverified only, or all comments
- **Edit window:** How long users can edit their comments
- **Max nesting depth:** How deep replies can nest
- **Rate limiting:** Comments per minute per IP
- **Blocked words:** Spam keyword list
- **SMTP:** Email notification settings

## License

MIT
