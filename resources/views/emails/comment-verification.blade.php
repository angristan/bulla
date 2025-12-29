<x-mail::message>
# Verify Your Email

Thank you for commenting on {{ $siteName }}. Please verify your email address to display a verified badge on your comment.

<x-mail::button :url="$verifyUrl">
Verify Email
</x-mail::button>

Your comment:
> {{ Str::limit(strip_tags($comment->body_html), 200) }}

If you didn't post this comment, you can safely ignore this email.

Thanks,<br>
{{ $siteName }}
</x-mail::message>
