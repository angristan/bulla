# Bulla

<p align="center">
  <img src="public/bulla.png" alt="Bulla" width="128" height="128">
</p>

A self-hosted comment system for static sites and blogs.

## Features

- **Simple embedding** - Single script tag to add comments
- **Markdown support** - GitHub-flavored markdown with live preview
- **Nested replies** - Configurable nesting depth
- **Sorting** - Sort by oldest, newest, or popular (by votes)
- **Dark mode** - Light, dark, or auto (follows system preference)
- **GitHub login** - Optional GitHub authentication for commenters
- **Email notifications** - Notify authors when they receive replies
- **Voting** - Optional upvotes and downvotes with duplicate prevention
- **Deep linking** - Link directly to any comment with `#comment-{id}`
- **Moderation** - Approve, spam, delete from admin panel or email
- **Admin comments** - Post as admin from your site when logged in
- **Spam protection** - Honeypot, rate limiting, blocked words (no 3rd party)
- **Import from isso** - Migrate your existing comments
- **Atom feeds** - Subscribe to comment threads
- **Dual database support** - SQLite (default) or PostgreSQL
- **Lightweight embed** - ~10KB gzipped

## Quick Start

### Docker (Recommended)

```bash
docker run -d \
  -p 8000:8000 \
  -v bulla_data:/app/database \
  -e APP_URL=https://comments.example.com \
  ghcr.io/your-username/bulla:latest
```

Then visit `http://localhost:8000/admin/setup` to configure.

### Embedding

Add to your website:

```html
<script
    src="https://comments.example.com/embed/embed.js"
    data-bulla="https://comments.example.com"
    async
></script>
<div id="bulla-thread"></div>
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
git clone https://github.com/your-username/bulla
cd bulla

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

- **Moderation mode:** None or all comments
- **Edit window:** How long users can edit their comments
- **Max nesting depth:** Visual nesting level (0-3), replies unlimited
- **Rate limiting:** Comments per minute per IP
- **Blocked words:** Spam keyword list
- **Accent color:** Customize the primary color for buttons and links
- **Custom CSS:** Inject custom styles into the embed widget
- **Allowed origins:** Control which domains can embed comments
- **GitHub OAuth:** Client ID and secret for commenter authentication

## License

MIT
