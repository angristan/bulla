# Embedding Marge

Add a comment section to any website with a single script tag.

## Basic Usage

Add this to your HTML where you want comments to appear:

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-marge="https://comments.example.com"
  async
></script>
<div id="marge-thread"></div>
```

That's it! Marge will automatically initialize and load comments for the current page.

## Configuration Options

### Script Attributes

| Attribute | Description | Default |
|-----------|-------------|---------|
| `data-marge` | **Required.** URL of your Marge instance | - |
| `data-marge-theme` | Color theme: `light`, `dark`, `auto` (reactive) | `auto` |
| `data-marge-sort` | Comment sort order: `oldest`, `newest`, `popular` | `oldest` |

### Examples

#### Force Dark Theme

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-marge="https://comments.example.com"
  data-marge-theme="dark"
  async
></script>
<div id="marge-thread"></div>
```

#### Light Theme Only

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-marge="https://comments.example.com"
  data-marge-theme="light"
  async
></script>
<div id="marge-thread"></div>
```

#### Show Newest Comments First

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-marge="https://comments.example.com"
  data-marge-sort="newest"
  async
></script>
<div id="marge-thread"></div>
```

#### Dynamic Theme Switching

The `data-marge-theme` attribute is reactive. Change it at runtime to update the theme:

```javascript
// Switch to dark theme
document.querySelector('script[data-marge]').setAttribute('data-marge-theme', 'dark');

// Switch to light theme
document.querySelector('script[data-marge]').setAttribute('data-marge-theme', 'light');
```

This is useful for syncing with your site's theme toggle.

## Manual Initialization

For more control, initialize manually:

```html
<script src="https://comments.example.com/embed/embed.js" async></script>
<div id="my-comments"></div>

<script>
  window.addEventListener('load', function() {
    Marge.init({
      baseUrl: 'https://comments.example.com',
      container: '#my-comments',
      uri: '/custom-uri',          // Override page identifier
      pageTitle: 'My Page Title',  // Override page title
      pageUrl: 'https://...',      // Override canonical URL
      theme: 'auto',               // 'light', 'dark', or 'auto'
      sort: 'oldest'               // 'oldest', 'newest', or 'popular'
    });
  });
</script>
```

## Page Identification

By default, Marge uses `window.location.pathname` to identify the page. All comments on `/blog/my-post` will be grouped together regardless of query strings or anchors.

Override this with the `uri` option if needed:

```javascript
Marge.init({
  baseUrl: 'https://comments.example.com',
  uri: '/custom/path'  // Your custom identifier
});
```

## Comment Counts

Display comment counts anywhere on your site:

```html
<a href="/blog/post-1">Post 1 (<span data-marge-count="/blog/post-1">0</span> comments)</a>
<a href="/blog/post-2">Post 2 (<span data-marge-count="/blog/post-2">0</span> comments)</a>

<script>
  // Fetch counts for all elements with data-marge-count
  fetch('https://comments.example.com/api/counts', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      uris: ['/blog/post-1', '/blog/post-2']
    })
  })
  .then(r => r.json())
  .then(counts => {
    Object.entries(counts).forEach(([uri, count]) => {
      document.querySelector(`[data-marge-count="${uri}"]`).textContent = count;
    });
  });
</script>
```

## Styling

Marge uses CSS custom properties for theming. Override them in your stylesheet:

```css
.marge-container {
  --marge-bg: #ffffff;
  --marge-text: #1a1a1a;
  --marge-muted: #6b7280;
  --marge-border: #e5e7eb;
  --marge-primary: #3b82f6;
  --marge-primary-hover: #2563eb;
  --marge-success: #10b981;
  --marge-error: #ef4444;
  --marge-error-bg: #fef2f2;
}

/* Dark theme */
.marge-theme-dark {
  --marge-bg: #1f2937;
  --marge-text: #f3f4f6;
  --marge-muted: #9ca3af;
  --marge-border: #374151;
  --marge-primary: #60a5fa;
  --marge-primary-hover: #3b82f6;
  --marge-error-bg: #7f1d1d;
}
```

## RSS/Atom Feeds

Marge provides Atom feeds for syndication:

- **Recent comments:** `https://comments.example.com/feed/recent.atom`
- **Per-thread:** `https://comments.example.com/feed/blog/my-post.atom`

Add a link to your HTML `<head>`:

```html
<link
  rel="alternate"
  type="application/atom+xml"
  title="Comments"
  href="https://comments.example.com/feed/blog/my-post.atom"
>
```

## Posting as Admin

When you're logged into the Marge admin panel, you can post comments as admin directly from your website. Admin comments display with an "Admin" badge.

**Requirements:**
- Your website and Marge must be on the same domain (or subdomain)
- You must be logged into the admin panel in the same browser
- CORS must be configured with `supports_credentials: true` (enabled by default)

When logged in as admin, you'll see a "Posting as Admin" indicator above the comment form. Admin comments are automatically approved regardless of moderation settings.

**Note:** If your site and Marge are on different domains, cookies won't be shared by default due to browser security restrictions.

## Security Considerations

### CORS

Marge restricts cross-origin requests to the configured site URL by default. You can change this in Admin > Settings by configuring allowed origins (use `*` to allow all domains).

### Content Security Policy

If you use CSP, allow these:

```
script-src 'self' comments.example.com;
style-src 'self' 'unsafe-inline' comments.example.com;
connect-src 'self' comments.example.com;
```

## Troubleshooting

### Comments not loading

1. Check browser console for errors
2. Verify `data-marge` URL is correct
3. Check CORS settings in admin panel

### Styling conflicts

Marge styles are scoped to `.marge-container` to minimize conflicts. If you have issues, increase specificity:

```css
#my-comments .marge-container {
  /* your overrides */
}
```
