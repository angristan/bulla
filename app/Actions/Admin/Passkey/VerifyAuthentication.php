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
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

class VerifyAuthentication
{
    use AsAction;

    /**
     * Verify WebAuthn authentication response.
     *
     * @param  array<string, mixed>  $credential
     * @return array{success: bool, message?: string}
     */
    public function handle(array $credential, string $challenge): array
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

            if (! $authenticatorResponse instanceof AuthenticatorAssertionResponse) {
                return ['success' => false, 'message' => 'Invalid response type'];
            }

            // Find the passkey by credential ID
            $credentialId = $publicKeyCredential->rawId;
            $passkey = Passkey::whereRaw('credential_id = ?', [$credentialId])->first();

            if (! $passkey) {
                return ['success' => false, 'message' => 'Passkey not found'];
            }

            // Get all passkeys for allowed credentials
            $passkeys = Passkey::all();
            $allowCredentials = $passkeys->map(function (Passkey $p) {
                return PublicKeyCredentialDescriptor::create(
                    type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                    id: $p->credential_id,
                    transports: $p->transports ?? [],
                );
            })->toArray();

            // Create the original options for validation
            $options = PublicKeyCredentialRequestOptions::create(
                challenge: base64_decode($challenge),
                rpId: $rpId,
                allowCredentials: $allowCredentials,
                userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            );

            // Create the credential source from stored passkey
            $adminEmail = Setting::getValue('admin_email', 'admin@localhost');
            $publicKeyCredentialSource = PublicKeyCredentialSource::create(
                publicKeyCredentialId: $passkey->credential_id,
                type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                transports: $passkey->transports ?? [],
                attestationType: 'none',
                trustPath: new \Webauthn\TrustPath\EmptyTrustPath,
                aaguid: \Symfony\Component\Uid\Uuid::v4(),
                credentialPublicKey: $passkey->public_key,
                userHandle: hash('sha256', $adminEmail, true),
                counter: $passkey->counter,
            );

            // Create algorithm manager
            $algorithmManager = Manager::create();
            $algorithmManager->add(ES256::create());
            $algorithmManager->add(RS256::create());

            // Create ceremony step manager
            $ceremonyStepManagerFactory = new CeremonyStepManagerFactory;
            $ceremonyStepManager = $ceremonyStepManagerFactory->requestCeremony(
                securedRelyingPartyId: [$rpId],
                allowedOrigins: [$siteUrl],
                algorithms: $algorithmManager,
            );

            // Validate the response
            $validator = AuthenticatorAssertionResponseValidator::create(
                ceremonyStepManager: $ceremonyStepManager,
            );

            $updatedSource = $validator->check(
                credentialId: $publicKeyCredential->rawId,
                authenticatorAssertionResponse: $authenticatorResponse,
                publicKeyCredentialRequestOptions: $options,
                host: $rpId,
                userHandle: null,
                publicKeyCredentialSource: $publicKeyCredentialSource,
            );

            // Update the counter
            $passkey->update(['counter' => $updatedSource->counter]);

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
