# Embedding Bulla

Add a comment section to any website with a single script tag.

## Basic Usage

Add this to your HTML where you want comments to appear:

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-bulla="https://comments.example.com"
  async
></script>
<div id="bulla-thread"></div>
```

That's it! Bulla will automatically initialize and load comments for the current page.

## Configuration Options

### Script Attributes

| Attribute | Description | Default |
|-----------|-------------|---------|
| `data-bulla` | **Required.** URL of your Bulla instance | - |
| `data-bulla-theme` | Color theme: `light`, `dark`, `auto` (reactive) | `auto` |
| `data-bulla-sort` | Comment sort order: `oldest`, `newest`, `popular` | `oldest` |
| `data-bulla-uri` | Custom page identifier | `window.location.pathname` |

### Examples

#### Force Dark Theme

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-bulla="https://comments.example.com"
  data-bulla-theme="dark"
  async
></script>
<div id="bulla-thread"></div>
```

#### Light Theme Only

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-bulla="https://comments.example.com"
  data-bulla-theme="light"
  async
></script>
<div id="bulla-thread"></div>
```

#### Show Newest Comments First

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-bulla="https://comments.example.com"
  data-bulla-sort="newest"
  async
></script>
<div id="bulla-thread"></div>
```

#### Dynamic Theme Switching

The `data-bulla-theme` attribute is reactive. Change it at runtime to update the theme:

```javascript
// Switch to dark theme
document.querySelector('script[data-bulla]').setAttribute('data-bulla-theme', 'dark');

// Switch to light theme
document.querySelector('script[data-bulla]').setAttribute('data-bulla-theme', 'light');
```

This is useful for syncing with your site's theme toggle.

## Manual Initialization

For more control, initialize manually:

```html
<script src="https://comments.example.com/embed/embed.js" async></script>
<div id="my-comments"></div>

<script>
  window.addEventListener('load', function() {
    Bulla.init({
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

By default, Bulla uses `window.location.pathname` to identify the page. All comments on `/blog/my-post` will be grouped together regardless of query strings or anchors.

Override this with the `data-bulla-uri` attribute:

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-bulla="https://comments.example.com"
  data-bulla-uri="/custom/path"
  async
></script>
```

Or with the `uri` option in manual initialization:

```javascript
Bulla.init({
  baseUrl: 'https://comments.example.com',
  uri: '/custom/path'
});
```

You can also set `data-uri` on the container element as a fallback:

```html
<div id="bulla-thread" data-uri="/custom/path"></div>
```

## Comment Counts

Display comment counts anywhere on your site:

```html
<a href="/blog/post-1">Post 1 (<span data-bulla-count="/blog/post-1">0</span> comments)</a>
<a href="/blog/post-2">Post 2 (<span data-bulla-count="/blog/post-2">0</span> comments)</a>

<script>
  // Fetch counts for all elements with data-bulla-count
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
      document.querySelector(`[data-bulla-count="${uri}"]`).textContent = count;
    });
  });
</script>
```

## Styling

### Accent Color

The primary accent color (used for buttons, links, and highlights) can be configured in **Admin > Settings > Appearance**.

### Custom CSS

For more control, Bulla uses CSS custom properties. The widget uses opacity-based colors to blend with your site's background. Override them in your stylesheet or via **Admin > Settings > Appearance > Custom CSS**:

```css
.bulla-container {
  /* Backgrounds use opacity to blend with your site */
  --bulla-bg: rgba(0, 0, 0, 0.03);           /* Slight darkening */
  --bulla-bg-elevated: rgba(0, 0, 0, 0.04);  /* For code blocks, etc. */
  --bulla-text: rgba(0, 0, 0, 0.87);
  --bulla-muted: rgba(0, 0, 0, 0.5);
  --bulla-border: rgba(0, 0, 0, 0.08);

  /* Accent colors (configurable in admin) */
  --bulla-primary: #3b82f6;
  --bulla-primary-hover: #2563eb;

  /* Status colors */
  --bulla-success: #10b981;
  --bulla-error: #ef4444;
  --bulla-error-bg: rgba(239, 68, 68, 0.08);
}

/* Dark theme */
.bulla-theme-dark {
  --bulla-bg: rgba(255, 255, 255, 0.04);           /* Slight lightening */
  --bulla-bg-elevated: rgba(255, 255, 255, 0.06);
  --bulla-text: rgba(255, 255, 255, 0.87);
  --bulla-muted: rgba(255, 255, 255, 0.5);
  --bulla-border: rgba(255, 255, 255, 0.08);
  --bulla-primary: #60a5fa;
  --bulla-primary-hover: #3b82f6;
  --bulla-error-bg: rgba(239, 68, 68, 0.12);
}
```

## Deep Linking

Link directly to any comment using the `#comment-{id}` anchor:

```
https://yourblog.com/post#comment-123
```

When a visitor opens a URL with a comment anchor, the page automatically scrolls to and highlights that comment.

**Features:**
- Click on any comment's date to copy its deep link URL
- The URL hash updates without reloading the page
- Email notifications link directly to the relevant comment
- Admin panel shows a "View on site" link for each comment

## RSS/Atom Feeds

Bulla provides Atom feeds for syndication:

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

When you're logged into the Bulla admin panel, you can post comments as admin directly from your website. Admin comments display with an "Admin" badge.

**Requirements:**
- Your website and Bulla must be on the same domain (or subdomain)
- You must be logged into the admin panel in the same browser
- CORS must be configured with `supports_credentials: true` (enabled by default)

When logged in as admin, you'll see a "Posting as Admin" indicator above the comment form. Admin comments are automatically approved regardless of moderation settings.

**Note:** If your site and Bulla are on different domains, cookies won't be shared by default due to browser security restrictions.

## Security Considerations

### CORS

Bulla restricts cross-origin requests to the configured site URL by default. You can change this in Admin > Settings by configuring allowed origins (use `*` to allow all domains).

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
2. Verify `data-bulla` URL is correct
3. Check CORS settings in admin panel

### Styling conflicts

Bulla styles are scoped to `.bulla-container` to minimize conflicts. If you have issues, increase specificity:

```css
#my-comments .bulla-container {
  /* your overrides */
}
```
