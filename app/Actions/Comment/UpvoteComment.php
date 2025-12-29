<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Models\Comment;
use App\Support\BloomFilter;
use Lorisleiva\Actions\Concerns\AsAction;

class UpvoteComment
{
    use AsAction;

    /**
     * Upvote a comment.
     * Returns the new upvote count, or null if already voted.
     */
    public function handle(Comment $comment, ?string $ip = null, ?string $userAgent = null): ?int
    {
        // Create voter identifier
        $voterId = BloomFilter::createVoterId($ip, $userAgent);

        // Load existing bloom filter
        $filter = BloomFilter::fromBinary($comment->voters_bloom);

        // Check if already voted
        if ($filter->mightContain($voterId)) {
            return null;
        }

        // Add to bloom filter
        $filter->add($voterId);

        // Update comment
        $comment->increment('upvotes');
        $comment->update(['voters_bloom' => $filter->toBinary()]);

        return $comment->upvotes;
    }
}
