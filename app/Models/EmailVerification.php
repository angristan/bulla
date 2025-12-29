<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $email
 * @property string $token
 * @property \Carbon\Carbon|null $verified_at
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EmailVerification extends Model
{
    protected $fillable = [
        'email',
        'token',
        'verified_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Create a new verification token for an email.
     */
    public static function createForEmail(string $email): self
    {
        return static::create([
            'email' => strtolower($email),
            'token' => Str::random(64),
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Check if this verification has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if this verification has been used.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Mark this verification as verified.
     */
    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    /**
     * Check if an email has been verified (within last 30 days).
     */
    public static function isEmailVerified(string $email): bool
    {
        return static::where('email', strtolower($email))
            ->whereNotNull('verified_at')
            ->where('verified_at', '>', now()->subDays(30))
            ->exists();
    }
}
