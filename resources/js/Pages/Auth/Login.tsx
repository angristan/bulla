import {
    Alert,
    Button,
    Center,
    Container,
    Image,
    Paper,
    Stack,
    Text,
} from '@mantine/core';
import { IconFingerprint } from '@tabler/icons-react';
import { useState } from 'react';

interface LoginProps {
    hasPasskeys: boolean;
}

// Helper to convert base64 to ArrayBuffer
function base64ToArrayBuffer(base64: string): ArrayBuffer {
    const binaryString = atob(base64);
    const bytes = new Uint8Array(binaryString.length);
    for (let i = 0; i < binaryString.length; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer;
}

// Helper to convert ArrayBuffer to base64
function arrayBufferToBase64(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary);
}

// Get CSRF token from cookie
function getCsrfToken(): string {
    const xsrfCookie = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));
    if (xsrfCookie) {
        return decodeURIComponent(xsrfCookie.split('=')[1]);
    }
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || ''
    );
}

export default function Login({ hasPasskeys }: LoginProps) {
    const [isAuthenticating, setIsAuthenticating] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handlePasskeyLogin = async () => {
        setIsAuthenticating(true);
        setError(null);

        try {
            // Get authentication options
            const optionsResponse = await fetch(
                '/admin/login/passkey/options',
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': getCsrfToken(),
                    },
                },
            );

            if (!optionsResponse.ok) {
                const data = await optionsResponse.json();
                throw new Error(
                    data.error || 'Failed to get authentication options',
                );
            }

            const options = await optionsResponse.json();

            // Convert base64 values to ArrayBuffer
            const publicKeyOptions: PublicKeyCredentialRequestOptions = {
                challenge: base64ToArrayBuffer(options.challenge),
                timeout: options.timeout,
                rpId: options.rpId,
                userVerification: options.userVerification,
                allowCredentials: options.allowCredentials?.map(
                    (cred: {
                        type: string;
                        id: string;
                        transports?: string[];
                    }) => ({
                        type: cred.type,
                        id: base64ToArrayBuffer(cred.id),
                        transports: cred.transports,
                    }),
                ),
            };

            // Request credential from browser
            const credential = (await navigator.credentials.get({
                publicKey: publicKeyOptions,
            })) as PublicKeyCredential;

            if (!credential) {
                throw new Error('No credential returned');
            }

            const response =
                credential.response as AuthenticatorAssertionResponse;

            // Prepare credential for server
            const credentialData = {
                id: credential.id,
                rawId: arrayBufferToBase64(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: arrayBufferToBase64(
                        response.clientDataJSON,
                    ),
                    authenticatorData: arrayBufferToBase64(
                        response.authenticatorData,
                    ),
                    signature: arrayBufferToBase64(response.signature),
                    userHandle: response.userHandle
                        ? arrayBufferToBase64(response.userHandle)
                        : null,
                },
            };

            // Verify credential with server
            const verifyResponse = await fetch('/admin/login/passkey/verify', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ credential: credentialData }),
            });

            const verifyData = await verifyResponse.json();

            if (!verifyResponse.ok) {
                throw new Error(verifyData.error || 'Verification failed');
            }

            // Redirect on success
            if (verifyData.redirect) {
                window.location.href = verifyData.redirect;
            }
        } catch (err) {
            if (err instanceof Error) {
                if (err.name === 'NotAllowedError') {
                    setError('Authentication was cancelled or timed out.');
                } else {
                    setError(err.message);
                }
            } else {
                setError('An unexpected error occurred');
            }
        } finally {
            setIsAuthenticating(false);
        }
    };

    if (!hasPasskeys) {
        return (
            <Container size={420} my={100}>
                <Center mb="xl">
                    <Image src="/bulla.png" alt="Bulla" w={64} h={64} />
                </Center>

                <Paper withBorder shadow="md" p={30} radius="md">
                    <Stack>
                        <Alert color="yellow">
                            No passkeys registered. Please complete the setup
                            wizard.
                        </Alert>
                        <Button component="a" href="/admin/setup" fullWidth>
                            Go to Setup
                        </Button>
                    </Stack>
                </Paper>
            </Container>
        );
    }

    return (
        <Container size={420} my={100}>
            <Center mb="xl">
                <Image src="/bulla.png" alt="Bulla" w={64} h={64} />
            </Center>

            <Paper withBorder shadow="md" p={30} radius="md">
                <Stack>
                    <Text ta="center" size="lg" fw={500}>
                        Sign in to your account
                    </Text>

                    {error && <Alert color="red">{error}</Alert>}

                    <Button
                        fullWidth
                        size="lg"
                        leftSection={<IconFingerprint size={20} />}
                        loading={isAuthenticating}
                        onClick={handlePasskeyLogin}
                    >
                        Sign in with Passkey
                    </Button>

                    <Text size="xs" c="dimmed" ta="center">
                        Use your fingerprint, face, or security key to sign in
                    </Text>
                </Stack>
            </Paper>
        </Container>
    );
}
