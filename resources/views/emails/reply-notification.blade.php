<x-mail::message>
# New Reply to Your Comment

Someone replied to your comment on {{ $siteName }}.

**{{ $reply->author ?? 'Anonymous' }}** wrote:
> {{ Str::limit(strip_tags($reply->body_html), 300) }}

In reply to your comment:
> {{ Str::limit(strip_tags($parentComment->body_html), 150) }}

<x-mail::button :url="$threadUrl">
View Reply
</x-mail::button>

---

<x-mail::subcopy>
[Unsubscribe]({{ $unsubscribeUrl }}) from reply notifications for this comment.
</x-mail::subcopy>

Thanks,<br>
{{ $siteName }}
</x-mail::message>
