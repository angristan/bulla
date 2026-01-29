import {
    Alert,
    Button,
    Center,
    Container,
    Group,
    Paper,
    Stack,
    Stepper,
    Text,
    TextInput,
    Title,
} from '@mantine/core';
import { IconFingerprint } from '@tabler/icons-react';
import { useCallback, useEffect, useState } from 'react';

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

export default function Setup() {
    const [active, setActive] = useState(0);
    const [data, setData] = useState({
        site_name: '',
        site_url: '',
        username: '',
        email: '',
        passkey_name: '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isProcessing, setIsProcessing] = useState(false);
    const [passkeyRegistered, setPasskeyRegistered] = useState(false);

    const updateData = (key: string, value: string) => {
        setData((prev) => ({ ...prev, [key]: value }));
        setErrors((prev) => ({ ...prev, [key]: '' }));
    };

    const nextStep = useCallback(
        () => setActive((current) => Math.min(current + 1, 3)),
        [],
    );
    const prevStep = () => setActive((current) => Math.max(current - 1, 0));

    const canProceed = useCallback(() => {
        switch (active) {
            case 0:
                return data.site_name.length > 0 && data.site_url.length > 0;
            case 1:
                return data.username.length >= 3 && data.email.length > 0;
            case 2:
                return passkeyRegistered;
            default:
                return true;
        }
    }, [active, data, passkeyRegistered]);

    const handleNextStep = useCallback(async () => {
        if (active === 1) {
            // Save info to session before proceeding to passkey step
            setIsProcessing(true);
            try {
                const response = await fetch('/admin/setup/info', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify({
                        site_name: data.site_name,
                        site_url: data.site_url,
                        username: data.username,
                        email: data.email,
                    }),
                });

                if (!response.ok) {
                    const result = await response.json();
                    if (result.errors) {
                        setErrors(result.errors);
                    } else {
                        setErrors({
                            general: result.error || 'Failed to save info',
                        });
                    }
                    return;
                }

                nextStep();
            } catch {
                setErrors({ general: 'An error occurred' });
            } finally {
                setIsProcessing(false);
            }
        } else {
            nextStep();
        }
    }, [active, data, nextStep]);

    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Enter' && active < 2 && canProceed()) {
                e.preventDefault();
                handleNextStep();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [active, canProceed, handleNextStep]);

    const handleRegisterPasskey = async () => {
        setIsProcessing(true);
        setErrors({});

        try {
            // Get registration options
            const optionsResponse = await fetch(
                '/admin/setup/passkey/options',
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
                const result = await optionsResponse.json();
                throw new Error(
                    result.error || 'Failed to get registration options',
                );
            }

            const options = await optionsResponse.json();

            // Convert base64 values to ArrayBuffer
            const publicKeyOptions: PublicKeyCredentialCreationOptions = {
                challenge: base64ToArrayBuffer(options.challenge),
                rp: {
                    name: options.rp.name,
                    id: options.rp.id,
                },
                user: {
                    id: base64ToArrayBuffer(options.user.id),
                    name: options.user.name,
                    displayName: options.user.displayName,
                },
                pubKeyCredParams: options.pubKeyCredParams,
                timeout: options.timeout,
                excludeCredentials: options.excludeCredentials?.map(
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
                authenticatorSelection: options.authenticatorSelection,
                attestation: options.attestation,
            };

            // Create credential
            const credential = (await navigator.credentials.create({
                publicKey: publicKeyOptions,
            })) as PublicKeyCredential;

            if (!credential) {
                throw new Error('No credential returned');
            }

            const response =
                credential.response as AuthenticatorAttestationResponse;

            // Prepare credential for server
            const credentialData = {
                id: credential.id,
                rawId: arrayBufferToBase64(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: arrayBufferToBase64(
                        response.clientDataJSON,
                    ),
                    attestationObject: arrayBufferToBase64(
                        response.attestationObject,
                    ),
                },
            };

            // Complete setup with passkey
            const setupResponse = await fetch('/admin/setup', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    credential: credentialData,
                    passkey_name: data.passkey_name || 'My Passkey',
                }),
            });

            const setupResult = await setupResponse.json();

            if (!setupResponse.ok) {
                throw new Error(setupResult.error || 'Setup failed');
            }

            setPasskeyRegistered(true);
            nextStep();

            // Redirect to dashboard after a moment
            if (setupResult.redirect) {
                setTimeout(() => {
                    window.location.href = setupResult.redirect;
                }, 1500);
            }
        } catch (err) {
            if (err instanceof Error) {
                if (err.name === 'NotAllowedError') {
                    setErrors({
                        passkey:
                            'Passkey registration was cancelled or timed out.',
                    });
                } else {
                    setErrors({ passkey: err.message });
                }
            } else {
                setErrors({ passkey: 'An unexpected error occurred' });
            }
        } finally {
            setIsProcessing(false);
        }
    };

    return (
        <Container size={600} my={50}>
            <Center mb="xl">
                <Title order={1}>Welcome to Bulla</Title>
            </Center>

            <Paper withBorder shadow="md" p={30} radius="md">
                <Stepper active={active} mb="xl">
                    <Stepper.Step
                        label="Site Info"
                        description="Configure your site"
                    >
                        <Stack mt="md">
                            <TextInput
                                label="Site Name"
                                placeholder="My Blog"
                                value={data.site_name}
                                onChange={(e) =>
                                    updateData('site_name', e.target.value)
                                }
                                error={errors.site_name}
                                required
                            />
                            <TextInput
                                label="Site URL"
                                description="The URL of your website where comments will be embedded"
                                placeholder="https://myblog.com"
                                value={data.site_url}
                                onChange={(e) =>
                                    updateData('site_url', e.target.value)
                                }
                                error={errors.site_url}
                                required
                            />
                        </Stack>
                    </Stepper.Step>

                    <Stepper.Step
                        label="Admin Account"
                        description="Create your account"
                    >
                        <Stack mt="md">
                            <TextInput
                                label="Username"
                                placeholder="admin"
                                value={data.username}
                                onChange={(e) =>
                                    updateData('username', e.target.value)
                                }
                                error={errors.username}
                                required
                                minLength={3}
                            />
                            <TextInput
                                label="Email"
                                placeholder="admin@example.com"
                                type="email"
                                value={data.email}
                                onChange={(e) =>
                                    updateData('email', e.target.value)
                                }
                                error={errors.email}
                                required
                            />
                        </Stack>
                    </Stepper.Step>

                    <Stepper.Step
                        label="Passkey"
                        description="Secure your account"
                    >
                        <Stack mt="md" align="center">
                            <Text size="lg" fw={500}>
                                Register a Passkey
                            </Text>
                            <Text c="dimmed" ta="center">
                                Passkeys use your device&apos;s biometrics or
                                security key to securely sign you in without a
                                password.
                            </Text>

                            {errors.passkey && (
                                <Alert color="red" w="100%">
                                    {errors.passkey}
                                </Alert>
                            )}
                            {errors.general && (
                                <Alert color="red" w="100%">
                                    {errors.general}
                                </Alert>
                            )}

                            <TextInput
                                label="Passkey Name"
                                description="A name to identify this passkey (e.g., 'MacBook', 'iPhone')"
                                placeholder="My Passkey"
                                value={data.passkey_name}
                                onChange={(e) =>
                                    updateData('passkey_name', e.target.value)
                                }
                                w="100%"
                            />

                            <Button
                                size="lg"
                                leftSection={<IconFingerprint size={20} />}
                                loading={isProcessing}
                                onClick={handleRegisterPasskey}
                                disabled={passkeyRegistered}
                            >
                                {passkeyRegistered
                                    ? 'Passkey Registered'
                                    : 'Register Passkey'}
                            </Button>
                        </Stack>
                    </Stepper.Step>

                    <Stepper.Step label="Complete" description="Finish setup">
                        <Stack mt="md" align="center">
                            <Text size="lg">You&apos;re all set!</Text>
                            <Text c="dimmed">
                                Redirecting to your dashboard...
                            </Text>
                        </Stack>
                    </Stepper.Step>
                </Stepper>

                <Group justify="space-between" mt="xl">
                    {active > 0 && active < 3 ? (
                        <Button
                            variant="default"
                            onClick={prevStep}
                            disabled={isProcessing}
                        >
                            Back
                        </Button>
                    ) : (
                        <div />
                    )}

                    {active < 2 && (
                        <Button
                            onClick={handleNextStep}
                            disabled={!canProceed()}
                            loading={isProcessing}
                        >
                            Next
                        </Button>
                    )}
                </Group>
            </Paper>
        </Container>
    );
}
