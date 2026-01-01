<x-mail::message>
# New Reply to Your Comment

Someone replied to your comment on {{ $siteName }}.

**{{ $reply->author ?? 'Anonymous' }}** wrote:

<x-mail::panel>
{{ Str::limit(strip_tags($reply->body_html), 300) }}
</x-mail::panel>

In reply to your comment:

<x-mail::panel>
{{ Str::limit(strip_tags($parentComment->body_html), 150) }}
</x-mail::panel>

<x-mail::button :url="$threadUrl">
View Reply
</x-mail::button>

<x-mail::subcopy>
[Unsubscribe from this comment]({{ $unsubscribeUrl }}) | [Unsubscribe from all]({{ $unsubscribeAllUrl }})
</x-mail::subcopy>

Thanks,<br>
{{ $siteName }}
</x-mail::message>
