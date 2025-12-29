<?php

declare(strict_types=1);

namespace App\Search;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;

class SqliteSearchDriver implements SearchDriver
{
    /**
     * @return Builder<Comment>
     */
    public function search(Builder $query, string $term): Builder
    {
        $searchTerm = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $q) use ($searchTerm): void {
            $q->where('body_markdown', 'LIKE', $searchTerm)
                ->orWhere('author', 'LIKE', $searchTerm)
                ->orWhere('email', 'LIKE', $searchTerm);
        });
    }
}
