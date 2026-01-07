<?php

declare(strict_types=1);

namespace App\Actions\Admin\Passkey;

use App\Models\Passkey;
use App\Models\Setting;
use Lorisleiva\Actions\Concerns\AsAction;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class GenerateRegistrationOptions
{
    use AsAction;

    /**
     * Generate WebAuthn registration options.
     *
     * @return array{options: array<string, mixed>, challenge: string}
     */
    public function handle(): array
    {
        $siteName = Setting::getValue('site_name', 'Bulla');
        $siteUrl = Setting::getValue('site_url', config('app.url'));
        $rpId = parse_url($siteUrl, PHP_URL_HOST) ?: 'localhost';

        $adminUsername = Setting::getValue('admin_username', 'admin');
        $adminEmail = Setting::getValue('admin_email', 'admin@localhost');

        // Create relying party entity
        $rpEntity = PublicKeyCredentialRpEntity::create(
            name: $siteName,
            id: $rpId,
        );

        // Create user entity - use email as ID for consistency
        $userEntity = PublicKeyCredentialUserEntity::create(
            name: $adminUsername,
            id: hash('sha256', $adminEmail, true),
            displayName: $adminUsername,
        );

        // Generate challenge
        $challenge = random_bytes(32);

        // Get existing credentials to exclude
        $excludeCredentials = Passkey::all()->map(function (Passkey $passkey) {
            return PublicKeyCredentialDescriptor::create(
                type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                id: $passkey->credential_id,
                transports: $passkey->transports ?? [],
            );
        })->toArray();

        // Supported algorithms (ES256, RS256)
        $pubKeyCredParams = [
            PublicKeyCredentialParameters::create(
                type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                alg: -7, // ES256
            ),
            PublicKeyCredentialParameters::create(
                type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                alg: -257, // RS256
            ),
        ];

        $options = PublicKeyCredentialCreationOptions::create(
            rp: $rpEntity,
            user: $userEntity,
            challenge: $challenge,
            pubKeyCredParams: $pubKeyCredParams,
            excludeCredentials: $excludeCredentials,
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
    private function serializeOptions(PublicKeyCredentialCreationOptions $options): array
    {
        $rp = $options->rp;
        $user = $options->user;

        return [
            'rp' => [
                'name' => $rp->name,
                'id' => $rp->id,
            ],
            'user' => [
                'id' => base64_encode($user->id),
                'name' => $user->name,
                'displayName' => $user->displayName,
            ],
            'challenge' => base64_encode($options->challenge),
            'pubKeyCredParams' => array_map(
                fn ($param) => ['type' => $param->type, 'alg' => $param->alg],
                $options->pubKeyCredParams
            ),
            'timeout' => $options->timeout,
            'excludeCredentials' => array_map(
                fn ($cred) => [
                    'type' => $cred->type,
                    'id' => base64_encode($cred->id),
                    'transports' => $cred->transports,
                ],
                $options->excludeCredentials
            ),
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'residentKey' => 'preferred',
                'userVerification' => 'preferred',
            ],
            'attestation' => 'none',
        ];
    }
}
