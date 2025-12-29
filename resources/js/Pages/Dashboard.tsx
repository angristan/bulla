import { Container, Title, Text, Paper, Group, Stack, ThemeIcon } from '@mantine/core';

export default function Dashboard() {
    return (
        <Container size="lg" py="xl">
            <Stack gap="lg">
                <div>
                    <Title order={1}>Opaska</Title>
                    <Text c="dimmed" size="lg">
                        Self-hosted comment system
                    </Text>
                </div>

                <Paper shadow="xs" p="xl" radius="md" withBorder>
                    <Stack gap="md">
                        <Title order={3}>Welcome to Opaska</Title>
                        <Text>
                            A modern, self-hosted comment system built with Laravel and React.
                        </Text>
                        <Text size="sm" c="dimmed">
                            This is a placeholder page. The admin dashboard will be built here.
                        </Text>
                    </Stack>
                </Paper>
            </Stack>
        </Container>
    );
}
