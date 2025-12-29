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
        // Sanitize the search term for tsquery
        $sanitized = $this->sanitizeForTsQuery($term);

        return $query->whereRaw(
            "to_tsvector('english', coalesce(body_markdown, '') || ' ' || coalesce(author, '')) @@ plainto_tsquery('english', ?)",
            [$sanitized]
        );
    }

    protected function sanitizeForTsQuery(string $term): string
    {
        // Remove special characters that could break tsquery
        return preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $term) ?? $term;
    }
}
