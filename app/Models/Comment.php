<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $thread_id
 * @property int|null $parent_id
 * @property string|null $author
 * @property string|null $email
 * @property string|null $website
 * @property bool $is_admin
 * @property string $body_markdown
 * @property string $body_html
 * @property string $status
 * @property bool $email_verified
 * @property int $upvotes
 * @property string|null $voters_bloom
 * @property bool $notify_replies
 * @property string|null $remote_addr
 * @property string|null $user_agent
 * @property string|null $edit_token
 * @property \Carbon\Carbon|null $edit_token_expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Thread $thread
 * @property-read Comment|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $replies
 */
class Comment extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SPAM = 'spam';

    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'thread_id',
        'parent_id',
        'author',
        'email',
        'website',
        'is_admin',
        'body_markdown',
        'body_html',
        'status',
        'email_verified',
        'upvotes',
        'voters_bloom',
        'notify_replies',
        'remote_addr',
        'user_agent',
        'edit_token',
        'edit_token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'email_verified' => 'boolean',
            'upvotes' => 'integer',
            'notify_replies' => 'boolean',
            'edit_token_expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Thread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isSpam(): bool
    {
        return $this->status === self::STATUS_SPAM;
    }

    public function canEdit(string $token): bool
    {
        if ($this->edit_token === null) {
            return false;
        }

        if ($this->edit_token_expires_at === null) {
            return false;
        }

        return hash_equals($this->edit_token, $token)
            && $this->edit_token_expires_at->isFuture();
    }
}
