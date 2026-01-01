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
- **Admin comments** - Post as admin from your site when logged in, claim past comments
- **Spam protection** - Honeypot, rate limiting, blocked words (no 3rd party)
- **Import/Export** - Migrate from isso, Disqus, or WordPress
- **Atom feeds** - Subscribe to comment threads
- **Dual database support** - SQLite (default) or PostgreSQL
- **Lightweight embed** - ~10KB gzipped

## Quick Start

Bulla is designed to be easily self-hosted. Just a single Docker container with SQLite. PostgreSQL and imgproxy are optional.

```bash
docker run -d \
  -p 8000:8000 \
  -v bulla_data:/app/database \
  -v bulla_storage:/app/storage \
  -e APP_URL=https://comments.example.com \
  ghcr.io/angristan/bulla:latest
```

To add comments to your site:

```html
<script
    src="https://comments.example.com/embed/embed.js"
    data-bulla="https://comments.example.com"
    async
></script>
<div id="bulla-thread"></div>
```

Learn more in the [Self-Hosting Guide](docs/self-hosting.md).

## Documentation

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
git clone https://github.com/angristan/bulla
cd bulla

# Set up environment
cp .env.example .env
php artisan key:generate
php artisan migrate

# Start development (installs deps, runs server + vite + embed watcher)
composer run dev
```

This runs concurrently:
- Laravel server (`php artisan serve`)
- Queue worker (`php artisan queue:listen`)
- Log viewer (`php artisan pail`)
- Vite for admin assets (`npm run dev`)
- Embed widget watcher (`npm run dev --prefix embed`)

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

## Importing Comments

Bulla supports importing from:
- **isso** - Upload your isso SQLite database file
- **Disqus** - Export your data from Disqus and upload the XML file
- **WordPress** - Export comments via WXR (WordPress eXtended RSS) format

To import: Go to Admin > Settings > Import, select your platform, and upload the file. Comments will be imported with original timestamps preserved.

After importing, use the "Claim Admin Comments" feature in Settings to mark your past comments as admin by matching email or author name.

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

## Acknowledgements

Inspired by [Isso](https://github.com/isso-comments/isso).

## License

MIT
