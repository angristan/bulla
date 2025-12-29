import { useForm } from '@inertiajs/react';
import {
    Button,
    Center,
    Container,
    Paper,
    PasswordInput,
    Stack,
    TextInput,
    Title,
} from '@mantine/core';
import type { FormEvent } from 'react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        password: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/login');
    };

    return (
        <Container size={420} my={100}>
            <Center mb="xl">
                <Title order={1}>Opaska</Title>
            </Center>

            <Paper withBorder shadow="md" p={30} radius="md">
                <form onSubmit={handleSubmit}>
                    <Stack>
                        <TextInput
                            label="Username"
                            placeholder="admin"
                            value={data.username}
                            onChange={(e) =>
                                setData('username', e.target.value)
                            }
                            error={errors.username}
                            required
                        />
                        <PasswordInput
                            label="Password"
                            placeholder="Your password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            error={errors.password}
                            required
                        />
                        <Button type="submit" fullWidth loading={processing}>
                            Sign in
                        </Button>
                    </Stack>
                </form>
            </Paper>
        </Container>
    );
}
