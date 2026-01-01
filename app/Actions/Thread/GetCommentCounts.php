<?php

declare(strict_types=1);

namespace App\Actions\Thread;

use App\Models\Comment;
use App\Models\Thread;
use Lorisleiva\Actions\Concerns\AsAction;

class GetCommentCounts
{
    use AsAction;

    /**
     * Get comment counts for multiple URIs.
     *
     * @param  array<string>  $uris
     * @return array<string, int>
     */
    public function handle(array $uris): array
    {
        // Normalize URIs (strip trailing slashes)
        $normalizedUris = array_map(fn ($uri) => '/'.trim($uri, '/'), $uris);

        // Build list of URIs to check (both with and without trailing slash)
        $urisToCheck = [];
        foreach ($normalizedUris as $uri) {
            $urisToCheck[] = $uri;
            $urisToCheck[] = $uri.'/';
        }

        $threads = Thread::whereIn('uri', $urisToCheck)
            ->withCount(['comments' => function ($query): void {
                $query->where('status', Comment::STATUS_APPROVED);
            }])
            ->get();

        // Build lookup map for both variants
        $threadsByUri = [];
        foreach ($threads as $thread) {
            $threadsByUri[$thread->uri] = $thread;
        }

        $counts = [];
        foreach ($normalizedUris as $i => $uri) {
            $originalUri = $uris[$i];
            // Check both with and without trailing slash
            $thread = $threadsByUri[$uri] ?? $threadsByUri[$uri.'/'] ?? null;
            $counts[$originalUri] = $thread ? $thread->comments_count : 0;
        }

        return $counts;
    }
}
