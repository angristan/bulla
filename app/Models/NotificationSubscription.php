<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $comment_id
 * @property string $email
 * @property string $unsubscribe_token
 * @property \Carbon\Carbon|null $unsubscribed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Comment $comment
 */
class NotificationSubscription extends Model
{
    protected $fillable = [
        'comment_id',
        'email',
        'unsubscribe_token',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Create a subscription for a comment.
     */
    public static function subscribe(Comment $comment, string $email): self
    {
        return static::firstOrCreate(
            [
                'comment_id' => $comment->id,
                'email' => strtolower($email),
            ],
            [
                'unsubscribe_token' => Str::random(64),
            ]
        );
    }

    /**
     * Check if this subscription is active.
     */
    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }

    /**
     * Unsubscribe from notifications.
     */
    public function unsubscribe(): void
    {
        $this->update(['unsubscribed_at' => now()]);
    }
}
