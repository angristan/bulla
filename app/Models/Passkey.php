<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $credential_id
 * @property string $public_key
 * @property int $counter
 * @property array<string>|null $transports
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Passkey extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'credential_id',
        'public_key',
        'counter',
        'transports',
    ];

    protected function casts(): array
    {
        return [
            'transports' => 'array',
            'counter' => 'integer',
        ];
    }
}
