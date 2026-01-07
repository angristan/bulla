<?php

declare(strict_types=1);

namespace App\Actions\Admin\Passkey;

use App\Models\Passkey;
use App\Models\Setting;
use Lorisleiva\Actions\Concerns\AsAction;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

class GenerateAuthenticationOptions
{
    use AsAction;

    /**
     * Generate WebAuthn authentication options.
     *
     * @return array{options: array<string, mixed>, challenge: string}|null
     */
    public function handle(): ?array
    {
        // Get all registered passkeys
        $passkeys = Passkey::all();

        if ($passkeys->isEmpty()) {
            return null;
        }

        $siteUrl = Setting::getValue('site_url', config('app.url'));
        $rpId = parse_url($siteUrl, PHP_URL_HOST) ?: 'localhost';

        // Generate challenge
        $challenge = random_bytes(32);

        // Create allowed credentials list
        $allowCredentials = $passkeys->map(function (Passkey $passkey) {
            return PublicKeyCredentialDescriptor::create(
                type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                id: $passkey->credential_id,
                transports: $passkey->transports ?? [],
            );
        })->toArray();

        $options = PublicKeyCredentialRequestOptions::create(
            challenge: $challenge,
            rpId: $rpId,
            allowCredentials: $allowCredentials,
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            timeout: 60000,
        );

        return [
            'options' => $this->serializeOptions($options),
            'challenge' => base64_encode($challenge),
        ];
    }

    /**
     * Serialize options to JSON-compatible array.
     *
     * @return array<string, mixed>
     */
    private function serializeOptions(PublicKeyCredentialRequestOptions $options): array
    {
        return [
            'challenge' => base64_encode($options->challenge),
            'timeout' => $options->timeout,
            'rpId' => $options->rpId,
            'allowCredentials' => array_map(
                fn ($cred) => [
                    'type' => $cred->type,
                    'id' => base64_encode($cred->id),
                    'transports' => $cred->transports,
                ],
                $options->allowCredentials
            ),
            'userVerification' => $options->userVerification,
        ];
    }
}
