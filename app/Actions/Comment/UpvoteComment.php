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

        // Update comment - set attributes and save in one operation
        $comment->upvotes = $comment->upvotes + 1;
        $comment->voters_bloom = $filter->toBinary();
        $comment->save();

        return $comment->upvotes;
    }
}
