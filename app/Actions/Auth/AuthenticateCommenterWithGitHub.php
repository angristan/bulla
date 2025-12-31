<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Lorisleiva\Actions\Concerns\AsAction;

class AuthenticateCommenterWithGitHub
{
    use AsAction;

    /**
     * Store GitHub user info in session.
     *
     * @param  array{
     *     id: string|int,
     *     name: string|null,
     *     email: string|null,
     *     avatar: string|null,
     *     nickname: string|null,
     * }  $githubUser
     */
    public function handle(array $githubUser): void
    {
        session()->put('commenter', [
            'github_id' => (string) $githubUser['id'],
            'github_username' => $githubUser['nickname'],
            'name' => $githubUser['name'] ?? $githubUser['nickname'],
            'email' => $githubUser['email'],
        ]);
    }
}
