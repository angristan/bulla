<x-mail::message>
# New Comment Pending Moderation

A new comment was posted on {{ $siteName }} and requires moderation.

**Author:** {{ $comment->author ?? 'Anonymous' }}
@if($comment->email)
**Email:** {{ $comment->email }}
@endif
**Thread:** {{ $comment->thread->uri }}

**Comment:**
> {{ Str::limit(strip_tags($comment->body_html), 500) }}

<x-mail::button :url="$approveUrl" color="success">
Approve
</x-mail::button>

<x-mail::button :url="$deleteUrl" color="error">
Delete
</x-mail::button>

[View in admin panel]({{ $adminUrl }}) | [View on site]({{ $threadUrl }})

Thanks,<br>
{{ $siteName }}
</x-mail::message>
