<?php

declare(strict_types=1);

namespace App\Actions\Admin\Passkey;

use App\Models\Passkey;
use App\Models\Setting;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Lorisleiva\Actions\Concerns\AsAction;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class VerifyRegistration
{
    use AsAction;

    /**
     * Verify WebAuthn registration response and store the passkey.
     *
     * @param  array<string, mixed>  $credential
     * @return array{success: bool, message?: string, passkey?: Passkey}
     */
    public function handle(array $credential, string $challenge, string $name = 'My Passkey'): array
    {
        try {
            $siteUrl = Setting::getValue('site_url', config('app.url'));
            $rpId = parse_url($siteUrl, PHP_URL_HOST) ?: 'localhost';

            // Create the serializer
            $attestationStatementSupportManager = AttestationStatementSupportManager::create();
            $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());

            $factory = new WebauthnSerializerFactory($attestationStatementSupportManager);
            $serializer = $factory->create();

            // Deserialize the credential
            $publicKeyCredential = $serializer->deserialize(
                json_encode($credential),
                PublicKeyCredential::class,
                'json'
            );

            $authenticatorResponse = $publicKeyCredential->response;

            if (! $authenticatorResponse instanceof AuthenticatorAttestationResponse) {
                return ['success' => false, 'message' => 'Invalid response type'];
            }

            // Create the original options for validation
            $siteName = Setting::getValue('site_name', 'Bulla');
            $adminUsername = Setting::getValue('admin_username', 'admin');
            $adminEmail = Setting::getValue('admin_email', 'admin@localhost');

            $rpEntity = PublicKeyCredentialRpEntity::create(
                name: $siteName,
                id: $rpId,
            );

            $userEntity = PublicKeyCredentialUserEntity::create(
                name: $adminUsername,
                id: hash('sha256', $adminEmail, true),
                displayName: $adminUsername,
            );

            $pubKeyCredParams = [
                PublicKeyCredentialParameters::create('public-key', -7),
                PublicKeyCredentialParameters::create('public-key', -257),
            ];

            $options = PublicKeyCredentialCreationOptions::create(
                rp: $rpEntity,
                user: $userEntity,
                challenge: base64_decode($challenge),
                pubKeyCredParams: $pubKeyCredParams,
            );

            // Create algorithm manager
            $algorithmManager = Manager::create();
            $algorithmManager->add(ES256::create());
            $algorithmManager->add(RS256::create());

            // Create ceremony step manager
            $ceremonyStepManagerFactory = new CeremonyStepManagerFactory;
            $ceremonyStepManager = $ceremonyStepManagerFactory->creationCeremony(
                securedRelyingPartyId: [$rpId],
                allowedOrigins: [$siteUrl],
                algorithms: $algorithmManager,
            );

            // Validate the response
            $validator = AuthenticatorAttestationResponseValidator::create(
                ceremonyStepManager: $ceremonyStepManager,
            );

            $publicKeyCredentialSource = $validator->check(
                authenticatorAttestationResponse: $authenticatorResponse,
                publicKeyCredentialCreationOptions: $options,
                host: $rpId,
            );

            // Store the passkey
            $passkey = Passkey::create([
                'name' => $name,
                'credential_id' => $publicKeyCredentialSource->publicKeyCredentialId,
                'public_key' => $publicKeyCredentialSource->credentialPublicKey,
                'counter' => $publicKeyCredentialSource->counter,
                'transports' => $publicKeyCredentialSource->transports,
            ]);

            return ['success' => true, 'passkey' => $passkey];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
