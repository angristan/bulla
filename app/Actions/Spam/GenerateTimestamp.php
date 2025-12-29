<?php

declare(strict_types=1);

namespace App\Actions\Spam;

use Illuminate\Support\Facades\Crypt;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateTimestamp
{
    use AsAction;

    /**
     * Generate a signed timestamp for form submission.
     */
    public function handle(): string
    {
        $timestamp = time();

        return Crypt::encryptString((string) $timestamp);
    }

    /**
     * Validate and extract timestamp from signed value.
     */
    public static function validate(string $signedTimestamp): ?int
    {
        try {
            $timestamp = (int) Crypt::decryptString($signedTimestamp);

            // Check if timestamp is reasonable (within last hour)
            if ($timestamp > time() - 3600 && $timestamp <= time()) {
                return $timestamp;
            }

            return null;
        } catch (\Exception) {
            return null;
        }
    }
}
