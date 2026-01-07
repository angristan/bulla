<?php

declare(strict_types=1);

namespace App\Actions\Admin\Passkey;

use App\Models\Passkey;
use Lorisleiva\Actions\Concerns\AsAction;

class GetPasskeyStatus
{
    use AsAction;

    /**
     * Get passkey authentication status.
     *
     * @return array{enabled: bool, passkeys: array<array{id: string, name: string, created_at: string}>}
     */
    public function handle(): array
    {
        $passkeys = Passkey::orderBy('created_at', 'desc')->get();

        return [
            'enabled' => $passkeys->isNotEmpty(),
            'passkeys' => $passkeys->map(fn (Passkey $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'created_at' => $p->created_at->diffForHumans(),
            ])->toArray(),
        ];
    }
}
