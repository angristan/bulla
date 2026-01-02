<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * @property string $key
 * @property string|null $value
 * @property bool $encrypted
 */
class Setting extends Model
{
    /**
     * Request-scoped cache for settings values.
     *
     * @var array<string, string|null>
     */
    protected static array $cache = [];

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'encrypted',
    ];

    protected function casts(): array
    {
        return [
            'encrypted' => 'boolean',
        ];
    }

    /**
     * Get the decrypted value.
     */
    public function getDecryptedValue(): ?string
    {
        if ($this->value === null) {
            return null;
        }

        if ($this->encrypted) {
            return Crypt::decryptString($this->value);
        }

        return $this->value;
    }

    /**
     * Set the value, optionally encrypting it.
     */
    public function setEncryptedValue(string $value, bool $encrypt = false): void
    {
        $this->encrypted = $encrypt;
        $this->value = $encrypt ? Crypt::encryptString($value) : $value;
    }

    /**
     * Get a setting value by key (cached per request).
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, static::$cache)) {
            return static::$cache[$key] ?? $default;
        }

        $setting = static::find($key);

        if ($setting === null) {
            static::$cache[$key] = null;

            return $default;
        }

        static::$cache[$key] = $setting->getDecryptedValue();

        return static::$cache[$key] ?? $default;
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $key, ?string $value, bool $encrypted = false): void
    {
        $setting = static::find($key) ?? new self(['key' => $key]);

        if ($value === null) {
            $setting->delete();
            unset(static::$cache[$key]);

            return;
        }

        $setting->setEncryptedValue($value, $encrypted);
        $setting->save();

        static::$cache[$key] = $value;
    }

    /**
     * Flush the settings cache (useful for testing and Octane).
     */
    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
