<?php

declare(strict_types=1);

namespace App\Search;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;

class PostgresSearchDriver implements SearchDriver
{
    /**
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function search(Builder $query, string $term): Builder
    {
        // Sanitize the search term for tsquery (strips special chars that break FTS)
        $sanitizedForFts = $this->sanitizeForTsQuery($term);
        // For ILIKE, escape SQL wildcards but keep other characters like @
        $sanitizedForLike = addcslashes($term, '%_');

        return $query->where(function (Builder $q) use ($sanitizedForFts, $sanitizedForLike): void {
            $q->whereRaw(
                "to_tsvector('english', coalesce(body_markdown, '') || ' ' || coalesce(author, '')) @@ plainto_tsquery('english', ?)",
                [$sanitizedForFts]
            )->orWhere('email', 'ILIKE', '%'.$sanitizedForLike.'%');
        });
    }

    protected function sanitizeForTsQuery(string $term): string
    {
        // Remove special characters that could break tsquery
        return preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $term) ?? $term;
    }
}
