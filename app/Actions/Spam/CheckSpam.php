<?php

declare(strict_types=1);

namespace App\Actions\Spam;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsAction;

class CheckSpam
{
    use AsAction;

    /**
     * Check if a comment submission is spam.
     * Returns null if not spam, or an error message if spam.
     *
     * @param  array{
     *     honeypot?: string|null,
     *     timestamp?: int|null,
     *     body: string,
     * }  $data
     */
    public function handle(array $data, ?string $ip = null): ?string
    {
        // Check honeypot
        if ($error = $this->checkHoneypot($data)) {
            return $error;
        }

        // Check time (too fast)
        if ($error = $this->checkTimestamp($data)) {
            return $error;
        }

        // Check rate limit
        if ($error = $this->checkRateLimit($ip)) {
            return $error;
        }

        // Check for blocked words
        if ($error = $this->checkBlockedWords($data['body'])) {
            return $error;
        }

        // Check for too many links
        if ($error = $this->checkLinkCount($data['body'])) {
            return $error;
        }

        return null;
    }

    /**
     * Check honeypot field.
     *
     * @param  array<string, mixed>  $data
     */
    private function checkHoneypot(array $data): ?string
    {
        if (isset($data['honeypot']) && $data['honeypot'] !== '') {
            return 'Invalid submission.';
        }

        return null;
    }

    /**
     * Check submission timestamp (minimum time).
     *
     * @param  array<string, mixed>  $data
     */
    private function checkTimestamp(array $data): ?string
    {
        if (! isset($data['timestamp'])) {
            return null;
        }

        $minTime = (int) Setting::getValue('spam_min_time_seconds', '3');
        $elapsed = time() - $data['timestamp'];

        if ($elapsed < $minTime) {
            return 'Please wait a moment before submitting.';
        }

        return null;
    }

    /**
     * Check rate limit for IP.
     */
    private function checkRateLimit(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        $maxPerMinute = (int) Setting::getValue('rate_limit_per_minute', '5');
        $key = 'comment_rate_'.$ip;

        $count = Cache::get($key, 0);

        if ($count >= $maxPerMinute) {
            return 'Too many comments. Please wait a minute.';
        }

        // Increment counter
        Cache::put($key, $count + 1, 60);

        return null;
    }

    /**
     * Check for blocked words.
     */
    private function checkBlockedWords(string $body): ?string
    {
        $blockedWords = Setting::getValue('blocked_words', '');

        if ($blockedWords === '' || $blockedWords === null) {
            return null;
        }

        $words = array_filter(array_map('trim', explode("\n", $blockedWords)));
        $bodyLower = strtolower($body);

        foreach ($words as $word) {
            if (str_contains($bodyLower, strtolower($word))) {
                return 'Your comment contains blocked content.';
            }
        }

        return null;
    }

    /**
     * Check link count in body.
     */
    private function checkLinkCount(string $body): ?string
    {
        $maxLinks = (int) Setting::getValue('max_links', '3');

        // Count URLs in body
        $pattern = '/https?:\/\/[^\s]+/i';
        preg_match_all($pattern, $body, $matches);

        if (count($matches[0]) > $maxLinks) {
            return 'Too many links in comment.';
        }

        return null;
    }
}
