# API Reference

Opaska provides a REST API for the embed widget and external integrations.

## Base URL

All API endpoints are prefixed with `/api`.

## Authentication

Public endpoints require no authentication. Admin endpoints require session authentication.

## Endpoints

### Get Comments

Retrieve comments for a thread.

```
GET /api/threads/{uri}/comments
```

**Parameters:**
- `uri` (path) - URL-encoded page path

**Response:**
```json
{
  "total": 5,
  "comments": [
    {
      "id": 1,
      "parent_id": null,
      "author": "John Doe",
      "avatar": "https://gravatar.com/avatar/...",
      "website": "https://example.com",
      "body_html": "<p>Comment content</p>",
      "status": "approved",
      "email_verified": true,
      "is_admin": false,
      "upvotes": 3,
      "created_at": "2025-01-15T10:30:00Z",
      "replies": []
    }
  ]
}
```

### Create Comment

Create a new comment.

```
POST /api/threads/{uri}/comments
```

**Body:**
```json
{
  "body": "Comment in **markdown**",
  "author": "Jane Doe",
  "email": "jane@example.com",
  "website": "https://jane.example.com",
  "parent_id": null,
  "notify_replies": true,
  "title": "Page Title",
  "url": "https://example.com/page",
  "timestamp": "signed_timestamp_from_api"
}
```

**Required:** `body`

**Response (201):**
```json
{
  "id": 2,
  "author": "Jane Doe",
  "avatar": "https://gravatar.com/avatar/...",
  "body_html": "<p>Comment in <strong>markdown</strong></p>",
  "status": "approved",
  "email_verified": false,
  "upvotes": 0,
  "created_at": "2025-01-15T11:00:00Z",
  "edit_token": "random_64_char_token",
  "edit_token_expires_at": "2025-01-15T11:15:00Z"
}
```

### Update Comment

Update a comment within the edit window.

```
PUT /api/comments/{id}
```

**Body:**
```json
{
  "body": "Updated comment",
  "author": "New Name",
  "edit_token": "token_from_create_response"
}
```

**Required:** `edit_token`

### Delete Comment

Delete a comment within the edit window.

```
DELETE /api/comments/{id}
```

**Body:**
```json
{
  "edit_token": "token_from_create_response"
}
```

### Upvote Comment

Upvote a comment (one per IP).

```
POST /api/comments/{id}/upvote
```

**Response:**
```json
{
  "upvotes": 4
}
```

### Preview Markdown

Preview markdown rendering.

```
POST /api/comments/preview
```

**Body:**
```json
{
  "body": "**bold** and *italic*"
}
```

**Response:**
```json
{
  "html": "<p><strong>bold</strong> and <em>italic</em></p>"
}
```

### Get Comment Counts

Get comment counts for multiple pages.

```
POST /api/counts
```

**Body:**
```json
{
  "uris": ["/blog/post-1", "/blog/post-2"]
}
```

**Response:**
```json
{
  "/blog/post-1": 5,
  "/blog/post-2": 12
}
```

### Get Config

Get public configuration.

```
GET /api/config
```

**Response:**
```json
{
  "site_name": "My Blog",
  "max_depth": 3,
  "require_author": false,
  "require_email": false,
  "edit_window_minutes": 15
}
```

### Get Timestamp

Get a signed timestamp for spam protection.

```
GET /api/timestamp
```

**Response:**
```json
{
  "timestamp": "signed_timestamp_string"
}
```

## Error Responses

### Validation Error (422)

```json
{
  "message": "The body field is required.",
  "errors": {
    "body": ["The body field is required."]
  }
}
```

### Rate Limited (429)

```json
{
  "message": "Too many requests. Please try again later."
}
```

### Spam Detected (422)

```json
{
  "message": "Your comment was flagged as spam.",
  "errors": {
    "body": ["Your comment was flagged as spam."]
  }
}
```

## Atom Feeds

### Recent Comments

```
GET /feed/recent.atom
```

Returns the 50 most recent approved comments across all threads.

### Thread Comments

```
GET /feed/{uri}.atom
```

Returns the 50 most recent approved comments for a specific thread.

**Example:** `/feed/blog/my-post.atom`

## Webhooks

Opaska does not currently support webhooks. Use Atom feeds for syndication.

## Rate Limits

- **Comment creation:** 5 per minute per IP (configurable)
- **Other endpoints:** No rate limits

## CORS

All API endpoints support CORS. Configure allowed origins in Admin > Settings.
