<x-mail::message>
# New Comment{{ $isPending ? ' (Pending Moderation)' : '' }}

A new comment was posted on {{ $siteName }}.

**Author:** {{ $comment->author ?? 'Anonymous' }}

@if($comment->email)
**Email:** {{ $comment->email }}

@endif
**Thread:** {{ $comment->thread->uri }}

<x-mail::panel>
{{ Str::limit(strip_tags($comment->body_html), 500) }}
</x-mail::panel>

@if($isPending)
<x-mail::button :url="$approveUrl" color="success">
Approve
</x-mail::button>
@endif

<x-mail::button :url="$deleteUrl" color="error">
Delete
</x-mail::button>

[View in admin]({{ $adminUrl }}) | [View on site]({{ $threadUrl }})

Thanks,<br>
{{ $siteName }}
</x-mail::message>
