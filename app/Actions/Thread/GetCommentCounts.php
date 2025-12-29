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
        // Normalize URIs
        $normalizedUris = array_map(fn ($uri) => '/'.trim($uri, '/'), $uris);

        $threads = Thread::whereIn('uri', $normalizedUris)
            ->withCount(['comments' => function ($query): void {
                $query->where('status', Comment::STATUS_APPROVED);
            }])
            ->get()
            ->keyBy('uri');

        $counts = [];
        foreach ($normalizedUris as $i => $uri) {
            $originalUri = $uris[$i];
            $thread = $threads->get($uri);
            $counts[$originalUri] = $thread ? $thread->comments_count : 0;
        }

        return $counts;
    }
}
