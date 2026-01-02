import { useForm } from '@inertiajs/react';
import {
    Button,
    Center,
    Container,
    Group,
    Paper,
    PasswordInput,
    Stack,
    Stepper,
    Text,
    TextInput,
    Title,
} from '@mantine/core';
import { type FormEvent, useCallback, useEffect, useState } from 'react';

export default function Setup() {
    const [active, setActive] = useState(0);
    const { data, setData, post, processing, errors } = useForm({
        site_name: '',
        site_url: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/setup');
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
                return (
                    data.username.length >= 3 &&
                    data.email.length > 0 &&
                    data.password.length >= 8 &&
                    data.password === data.password_confirmation
                );
            default:
                return true;
        }
    }, [active, data]);

    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Enter' && active < 2 && canProceed()) {
                e.preventDefault();
                nextStep();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [active, canProceed, nextStep]);

    return (
        <Container size={600} my={50}>
            <Center mb="xl">
                <Title order={1}>Welcome to Bulla</Title>
            </Center>

            <Paper withBorder shadow="md" p={30} radius="md">
                <form onSubmit={handleSubmit}>
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
                                        setData('site_name', e.target.value)
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
                                        setData('site_url', e.target.value)
                                    }
                                    error={errors.site_url}
                                    required
                                />
                            </Stack>
                        </Stepper.Step>

                        <Stepper.Step
                            label="Admin Account"
                            description="Create admin credentials"
                        >
                            <Stack mt="md">
                                <TextInput
                                    label="Username"
                                    placeholder="admin"
                                    value={data.username}
                                    onChange={(e) =>
                                        setData('username', e.target.value)
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
                                        setData('email', e.target.value)
                                    }
                                    error={errors.email}
                                    required
                                />
                                <PasswordInput
                                    label="Password"
                                    placeholder="Min 8 characters"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                    error={errors.password}
                                    required
                                    minLength={8}
                                />
                                <PasswordInput
                                    label="Confirm Password"
                                    placeholder="Repeat password"
                                    value={data.password_confirmation}
                                    onChange={(e) =>
                                        setData(
                                            'password_confirmation',
                                            e.target.value,
                                        )
                                    }
                                    error={
                                        data.password !==
                                        data.password_confirmation
                                            ? 'Passwords do not match'
                                            : undefined
                                    }
                                    required
                                />
                            </Stack>
                        </Stepper.Step>

                        <Stepper.Step
                            label="Complete"
                            description="Finish setup"
                        >
                            <Stack mt="md" align="center">
                                <Text size="lg">You&apos;re all set!</Text>
                                <Text c="dimmed">
                                    Click Complete to finish setup and access
                                    your dashboard.
                                </Text>
                            </Stack>
                        </Stepper.Step>
                    </Stepper>

                    <Group justify="space-between" mt="xl">
                        {active > 0 ? (
                            <Button variant="default" onClick={prevStep}>
                                Back
                            </Button>
                        ) : (
                            <div />
                        )}

                        {active < 2 ? (
                            <Button onClick={nextStep} disabled={!canProceed()}>
                                Next
                            </Button>
                        ) : (
                            <Button type="submit" loading={processing}>
                                Complete Setup
                            </Button>
                        )}
                    </Group>
                </form>
            </Paper>
        </Container>
    );
}
