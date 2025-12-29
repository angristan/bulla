<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uri
 * @property string|null $title
 * @property string|null $url
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $comments
 */
class Thread extends Model
{
    protected $fillable = [
        'uri',
        'title',
        'url',
    ];

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get approved comments count.
     */
    public function approvedCommentsCount(): int
    {
        return $this->comments()->where('status', 'approved')->count();
    }
}
