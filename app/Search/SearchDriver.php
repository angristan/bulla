<?php

declare(strict_types=1);

namespace App\Search;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;

interface SearchDriver
{
    /**
     * Apply search filter to a query builder.
     *
     * @return Builder<Comment>
     */
    public function search(Builder $query, string $term): Builder;
}
