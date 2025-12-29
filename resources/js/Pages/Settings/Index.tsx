import AdminLayout from '@/Layouts/AdminLayout';
import { useForm } from '@inertiajs/react';
import {
    Alert,
    Button,
    Group,
    NumberInput,
    Paper,
    PasswordInput,
    Select,
    Stack,
    Switch,
    Tabs,
    Text,
    TextInput,
    Textarea,
    Title,
} from '@mantine/core';
import { notifications } from '@mantine/notifications';
import {
    IconCheck,
    IconMail,
    IconPalette,
    IconSettings,
    IconShield,
} from '@tabler/icons-react';
import type { FormEvent } from 'react';

interface SettingsIndexProps {
    settings: {
        site_name: string;
        site_url: string | null;
        moderation_mode: string;
        require_author: boolean;
        require_email: boolean;
        max_depth: number;
        edit_window_minutes: number;
        rate_limit_per_minute: number;
        spam_min_time_seconds: number;
        max_links: number;
        blocked_words: string;
        blocked_ips: string;
        allowed_origins: string;
        custom_css: string;
        smtp_host: string | null;
        smtp_port: string;
        smtp_username: string | null;
        smtp_from_address: string | null;
        smtp_from_name: string | null;
        smtp_configured: boolean;
    };
}

export default function SettingsIndex({ settings }: SettingsIndexProps) {
    const { data, setData, post, processing, recentlySuccessful } = useForm({
        site_name: settings.site_name,
        site_url: settings.site_url || '',
        moderation_mode: settings.moderation_mode,
        require_author: settings.require_author,
        require_email: settings.require_email,
        max_depth: settings.max_depth,
        edit_window_minutes: settings.edit_window_minutes,
        rate_limit_per_minute: settings.rate_limit_per_minute,
        spam_min_time_seconds: settings.spam_min_time_seconds,
        max_links: settings.max_links,
        blocked_words: settings.blocked_words,
        blocked_ips: settings.blocked_ips,
        allowed_origins: settings.allowed_origins,
        custom_css: settings.custom_css,
        smtp_host: settings.smtp_host || '',
        smtp_port: settings.smtp_port,
        smtp_username: settings.smtp_username || '',
        smtp_password: '',
        smtp_from_address: settings.smtp_from_address || '',
        smtp_from_name: settings.smtp_from_name || '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/settings', {
            onSuccess: () => {
                notifications.show({
                    title: 'Settings saved',
                    message: 'Your settings have been updated successfully.',
                    color: 'green',
                    icon: <IconCheck size={16} />,
                });
            },
        });
    };

    return (
        <AdminLayout>
            <Title order={2} mb="lg">
                Settings
            </Title>

            <form onSubmit={handleSubmit}>
                <Tabs defaultValue="general">
                    <Tabs.List mb="lg">
                        <Tabs.Tab
                            value="general"
                            leftSection={<IconSettings size={16} />}
                        >
                            General
                        </Tabs.Tab>
                        <Tabs.Tab
                            value="moderation"
                            leftSection={<IconShield size={16} />}
                        >
                            Moderation
                        </Tabs.Tab>
                        <Tabs.Tab
                            value="email"
                            leftSection={<IconMail size={16} />}
                        >
                            Email
                        </Tabs.Tab>
                        <Tabs.Tab
                            value="appearance"
                            leftSection={<IconPalette size={16} />}
                        >
                            Appearance
                        </Tabs.Tab>
                    </Tabs.List>

                    <Tabs.Panel value="general">
                        <Paper withBorder p="md" radius="md">
                            <Stack>
                                <TextInput
                                    label="Site Name"
                                    description="The name of your site"
                                    value={data.site_name}
                                    onChange={(e) =>
                                        setData('site_name', e.target.value)
                                    }
                                />
                                <TextInput
                                    label="Site URL"
                                    description="The URL of your site (used for links in emails)"
                                    value={data.site_url}
                                    onChange={(e) =>
                                        setData('site_url', e.target.value)
                                    }
                                />
                                <TextInput
                                    label="Allowed Origins"
                                    description="Comma-separated list of domains allowed to embed comments (* for all)"
                                    value={data.allowed_origins}
                                    onChange={(e) =>
                                        setData(
                                            'allowed_origins',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Stack>
                        </Paper>
                    </Tabs.Panel>

                    <Tabs.Panel value="moderation">
                        <Paper withBorder p="md" radius="md">
                            <Stack>
                                <Select
                                    label="Moderation Mode"
                                    description="When to require manual approval"
                                    data={[
                                        {
                                            value: 'none',
                                            label: 'None - All comments auto-approved',
                                        },
                                        {
                                            value: 'unverified',
                                            label: 'Unverified - Only unverified emails need approval',
                                        },
                                        {
                                            value: 'all',
                                            label: 'All - All comments need approval',
                                        },
                                    ]}
                                    value={data.moderation_mode}
                                    onChange={(value) =>
                                        setData(
                                            'moderation_mode',
                                            value || 'none',
                                        )
                                    }
                                />
                                <Switch
                                    label="Require author name"
                                    checked={data.require_author}
                                    onChange={(e) =>
                                        setData(
                                            'require_author',
                                            e.target.checked,
                                        )
                                    }
                                />
                                <Switch
                                    label="Require email"
                                    checked={data.require_email}
                                    onChange={(e) =>
                                        setData(
                                            'require_email',
                                            e.target.checked,
                                        )
                                    }
                                />
                                <NumberInput
                                    label="Max reply depth"
                                    description="Maximum nesting level for replies"
                                    value={data.max_depth}
                                    onChange={(value) =>
                                        setData('max_depth', Number(value))
                                    }
                                    min={1}
                                    max={10}
                                />
                                <NumberInput
                                    label="Edit window (minutes)"
                                    description="How long users can edit their comments"
                                    value={data.edit_window_minutes}
                                    onChange={(value) =>
                                        setData(
                                            'edit_window_minutes',
                                            Number(value),
                                        )
                                    }
                                    min={0}
                                    max={1440}
                                />
                                <NumberInput
                                    label="Rate limit (comments per minute)"
                                    value={data.rate_limit_per_minute}
                                    onChange={(value) =>
                                        setData(
                                            'rate_limit_per_minute',
                                            Number(value),
                                        )
                                    }
                                    min={1}
                                    max={100}
                                />
                                <NumberInput
                                    label="Minimum submission time (seconds)"
                                    description="Reject comments submitted faster than this"
                                    value={data.spam_min_time_seconds}
                                    onChange={(value) =>
                                        setData(
                                            'spam_min_time_seconds',
                                            Number(value),
                                        )
                                    }
                                    min={0}
                                    max={60}
                                />
                                <NumberInput
                                    label="Maximum links per comment"
                                    value={data.max_links}
                                    onChange={(value) =>
                                        setData('max_links', Number(value))
                                    }
                                    min={0}
                                    max={50}
                                />
                                <Textarea
                                    label="Blocked words"
                                    description="One word/phrase per line"
                                    value={data.blocked_words}
                                    onChange={(e) =>
                                        setData('blocked_words', e.target.value)
                                    }
                                    minRows={4}
                                />
                                <Textarea
                                    label="Blocked IPs"
                                    description="One IP per line"
                                    value={data.blocked_ips}
                                    onChange={(e) =>
                                        setData('blocked_ips', e.target.value)
                                    }
                                    minRows={4}
                                />
                            </Stack>
                        </Paper>
                    </Tabs.Panel>

                    <Tabs.Panel value="email">
                        <Paper withBorder p="md" radius="md">
                            <Stack>
                                {settings.smtp_configured && (
                                    <Alert color="green">
                                        SMTP is configured. Leave password empty
                                        to keep existing.
                                    </Alert>
                                )}
                                <TextInput
                                    label="SMTP Host"
                                    value={data.smtp_host}
                                    onChange={(e) =>
                                        setData('smtp_host', e.target.value)
                                    }
                                />
                                <TextInput
                                    label="SMTP Port"
                                    value={data.smtp_port}
                                    onChange={(e) =>
                                        setData('smtp_port', e.target.value)
                                    }
                                />
                                <TextInput
                                    label="SMTP Username"
                                    value={data.smtp_username}
                                    onChange={(e) =>
                                        setData('smtp_username', e.target.value)
                                    }
                                />
                                <PasswordInput
                                    label="SMTP Password"
                                    value={data.smtp_password}
                                    onChange={(e) =>
                                        setData('smtp_password', e.target.value)
                                    }
                                    placeholder={
                                        settings.smtp_configured
                                            ? '••••••••'
                                            : ''
                                    }
                                />
                                <TextInput
                                    label="From Address"
                                    value={data.smtp_from_address}
                                    onChange={(e) =>
                                        setData(
                                            'smtp_from_address',
                                            e.target.value,
                                        )
                                    }
                                />
                                <TextInput
                                    label="From Name"
                                    value={data.smtp_from_name}
                                    onChange={(e) =>
                                        setData(
                                            'smtp_from_name',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Stack>
                        </Paper>
                    </Tabs.Panel>

                    <Tabs.Panel value="appearance">
                        <Paper withBorder p="md" radius="md">
                            <Stack>
                                <Textarea
                                    label="Custom CSS"
                                    description="CSS to inject into the embed widget"
                                    value={data.custom_css}
                                    onChange={(e) =>
                                        setData('custom_css', e.target.value)
                                    }
                                    minRows={10}
                                    styles={{
                                        input: { fontFamily: 'monospace' },
                                    }}
                                />
                            </Stack>
                        </Paper>
                    </Tabs.Panel>
                </Tabs>

                <Group justify="flex-end" mt="lg">
                    <Button type="submit" loading={processing}>
                        Save Settings
                    </Button>
                </Group>
            </form>
        </AdminLayout>
    );
}
