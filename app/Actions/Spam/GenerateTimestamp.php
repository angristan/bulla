<?php

declare(strict_types=1);

namespace App\Actions\Spam;

use Illuminate\Support\Facades\Crypt;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateTimestamp
{
    use AsAction;

    public const MAX_AGE_SECONDS = 86400;

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

            // Accept tokens up to a day old. A missing or invalid token flags the
            // comment as spam, so the window must cover readers who leave the page
            // open for a long time before posting.
            if ($timestamp > time() - self::MAX_AGE_SECONDS && $timestamp <= time()) {
                return $timestamp;
            }

            return null;
        } catch (\Exception) {
            return null;
        }
    }
}
