<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $source
 * @property string $source_id
 * @property string $target_type
 * @property int $target_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ImportMapping extends Model
{
    public const SOURCE_ISSO = 'isso';

    public const SOURCE_DISQUS = 'disqus';

    public const SOURCE_WORDPRESS = 'wordpress';

    public const SOURCE_JSON = 'json';

    public const TARGET_THREAD = 'thread';

    public const TARGET_COMMENT = 'comment';

    protected $fillable = [
        'source',
        'source_id',
        'target_type',
        'target_id',
    ];

    /**
     * Find the mapped target ID for a source.
     */
    public static function findTarget(string $source, string $sourceId, string $targetType): ?int
    {
        $mapping = static::where('source', $source)
            ->where('source_id', $sourceId)
            ->where('target_type', $targetType)
            ->first();

        return $mapping?->target_id;
    }

    /**
     * Create a mapping.
     */
    public static function createMapping(
        string $source,
        string $sourceId,
        string $targetType,
        int $targetId
    ): self {
        return static::create([
            'source' => $source,
            'source_id' => $sourceId,
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);
    }
}
