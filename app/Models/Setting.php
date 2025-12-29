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
     * Get a setting value by key.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::find($key);

        if ($setting === null) {
            return $default;
        }

        return $setting->getDecryptedValue() ?? $default;
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $key, ?string $value, bool $encrypted = false): void
    {
        $setting = static::find($key) ?? new static(['key' => $key]);

        if ($value === null) {
            $setting->delete();

            return;
        }

        $setting->setEncryptedValue($value, $encrypted);
        $setting->save();
    }
}
