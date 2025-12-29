<?php

declare(strict_types=1);

namespace App\Support;

class Gravatar
{
    /**
     * Generate a Gravatar URL for an email address.
     */
    public static function url(?string $email, int $size = 80): string
    {
        if ($email === null || $email === '') {
            return self::defaultUrl($size);
        }

        $hash = md5(strtolower(trim($email)));

        return sprintf(
            'https://www.gravatar.com/avatar/%s?s=%d&d=identicon&r=g',
            $hash,
            $size
        );
    }

    /**
     * Get the default avatar URL.
     */
    public static function defaultUrl(int $size = 80): string
    {
        // Generate a random hash for anonymous users
        $hash = md5((string) random_int(0, PHP_INT_MAX));

        return sprintf(
            'https://www.gravatar.com/avatar/%s?s=%d&d=identicon&r=g&f=y',
            $hash,
            $size
        );
    }

    /**
     * Generate an identicon-style hash for an IP address.
     * This gives consistent avatars for anonymous users within a thread.
     */
    public static function hashForIp(?string $ip, string $salt = ''): string
    {
        if ($ip === null) {
            return md5((string) random_int(0, PHP_INT_MAX));
        }

        return md5($ip.$salt);
    }
}
