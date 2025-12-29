# Embedding Opaska

Add a comment section to any website with a single script tag.

## Basic Usage

Add this to your HTML where you want comments to appear:

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-opaska="https://comments.example.com"
  async
></script>
<div id="opaska-thread"></div>
```

That's it! Opaska will automatically initialize and load comments for the current page.

## Configuration Options

### Script Attributes

| Attribute | Description | Default |
|-----------|-------------|---------|
| `data-opaska` | **Required.** URL of your Opaska instance | - |
| `data-opaska-theme` | Color theme: `light`, `dark`, `auto` | `auto` |

### Examples

#### Force Dark Theme

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-opaska="https://comments.example.com"
  data-opaska-theme="dark"
  async
></script>
<div id="opaska-thread"></div>
```

#### Light Theme Only

```html
<script
  src="https://comments.example.com/embed/embed.js"
  data-opaska="https://comments.example.com"
  data-opaska-theme="light"
  async
></script>
<div id="opaska-thread"></div>
```

## Manual Initialization

For more control, initialize manually:

```html
<script src="https://comments.example.com/embed/embed.js" async></script>
<div id="my-comments"></div>

<script>
  window.addEventListener('load', function() {
    Opaska.init({
      baseUrl: 'https://comments.example.com',
      container: '#my-comments',
      uri: '/custom-uri',          // Override page identifier
      pageTitle: 'My Page Title',  // Override page title
      pageUrl: 'https://...',      // Override canonical URL
      theme: 'auto'                // 'light', 'dark', or 'auto'
    });
  });
</script>
```

## Page Identification

By default, Opaska uses `window.location.pathname` to identify the page. All comments on `/blog/my-post` will be grouped together regardless of query strings or anchors.

Override this with the `uri` option if needed:

```javascript
Opaska.init({
  baseUrl: 'https://comments.example.com',
  uri: '/custom/path'  // Your custom identifier
});
```

## Comment Counts

Display comment counts anywhere on your site:

```html
<a href="/blog/post-1">Post 1 (<span data-opaska-count="/blog/post-1">0</span> comments)</a>
<a href="/blog/post-2">Post 2 (<span data-opaska-count="/blog/post-2">0</span> comments)</a>

<script>
  // Fetch counts for all elements with data-opaska-count
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
      document.querySelector(`[data-opaska-count="${uri}"]`).textContent = count;
    });
  });
</script>
```

## Styling

Opaska uses CSS custom properties for theming. Override them in your stylesheet:

```css
.opaska-container {
  --opaska-bg: #ffffff;
  --opaska-text: #1a1a1a;
  --opaska-muted: #6b7280;
  --opaska-border: #e5e7eb;
  --opaska-primary: #3b82f6;
  --opaska-primary-hover: #2563eb;
  --opaska-success: #10b981;
  --opaska-error: #ef4444;
  --opaska-error-bg: #fef2f2;
}

/* Dark theme */
.opaska-theme-dark {
  --opaska-bg: #1f2937;
  --opaska-text: #f3f4f6;
  --opaska-muted: #9ca3af;
  --opaska-border: #374151;
  --opaska-primary: #60a5fa;
  --opaska-primary-hover: #3b82f6;
  --opaska-error-bg: #7f1d1d;
}
```

## RSS/Atom Feeds

Opaska provides Atom feeds for syndication:

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

## Security Considerations

### CORS

Opaska allows cross-origin requests from any domain by default. You can restrict this in Admin > Settings by configuring allowed origins.

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
2. Verify `data-opaska` URL is correct
3. Check CORS settings in admin panel

### Styling conflicts

Opaska styles are scoped to `.opaska-container` to minimize conflicts. If you have issues, increase specificity:

```css
#my-comments .opaska-container {
  /* your overrides */
}
```
