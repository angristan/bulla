import { router, useForm } from '@inertiajs/react';
import {
    Alert,
    Button,
    ColorInput,
    Group,
    Modal,
    NumberInput,
    Paper,
    PasswordInput,
    Select,
    Stack,
    Switch,
    Tabs,
    Text,
    Textarea,
    TextInput,
    Title,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { notifications } from '@mantine/notifications';
import {
    IconAlertTriangle,
    IconBrandGithub,
    IconCheck,
    IconMail,
    IconPalette,
    IconSettings,
    IconShield,
    IconTrash,
} from '@tabler/icons-react';
import { type FormEvent, useState } from 'react';
import { useUrlState } from '@/hooks/useUrlState';
import AdminLayout from '@/Layouts/AdminLayout';

interface SettingsIndexProps {
    settings: {
        site_name: string;
        site_url: string | null;
        admin_display_name: string;
        admin_badge_label: string;
        admin_email: string | null;
        moderation_mode: string;
        require_author: boolean;
        require_email: boolean;
        max_depth: number;
        edit_window_minutes: number;
        rate_limit_per_minute: number;
        spam_min_time_seconds: number;
        blocked_words: string;
        blocked_ips: string;
        allowed_origins: string;
        custom_css: string;
        accent_color: string;
        enable_upvotes: boolean;
        enable_downvotes: boolean;
        enable_github_login: boolean;
        github_client_id: string | null;
        github_configured: boolean;
        smtp_host: string | null;
        smtp_port: string;
        smtp_username: string | null;
        smtp_from_address: string | null;
        smtp_from_name: string | null;
        smtp_configured: boolean;
    };
}

type SettingsTab = 'general' | 'moderation' | 'auth' | 'email' | 'appearance';

export default function SettingsIndex({ settings }: SettingsIndexProps) {
    const [activeTab, setActiveTab] = useUrlState<SettingsTab>(
        'tab',
        'general',
    );
    const [wipeModalOpened, { open: openWipeModal, close: closeWipeModal }] =
        useDisclosure(false);
    const [wipeConfirmation, setWipeConfirmation] = useState('');
    const [isWiping, setIsWiping] = useState(false);

    const { data, setData, post, processing } = useForm({
        site_name: settings.site_name,
        site_url: settings.site_url || '',
        admin_display_name: settings.admin_display_name,
        admin_badge_label: settings.admin_badge_label,
        admin_email: settings.admin_email || '',
        moderation_mode: settings.moderation_mode,
        require_author: settings.require_author,
        require_email: settings.require_email,
        max_depth: settings.max_depth,
        edit_window_minutes: settings.edit_window_minutes,
        rate_limit_per_minute: settings.rate_limit_per_minute,
        spam_min_time_seconds: settings.spam_min_time_seconds,
        blocked_words: settings.blocked_words,
        blocked_ips: settings.blocked_ips,
        allowed_origins: settings.allowed_origins,
        custom_css: settings.custom_css,
        accent_color: settings.accent_color,
        enable_upvotes: settings.enable_upvotes,
        enable_downvotes: settings.enable_downvotes,
        enable_github_login: settings.enable_github_login,
        github_client_id: settings.github_client_id || '',
        github_client_secret: '',
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

    const handleWipe = () => {
        setIsWiping(true);
        router.delete('/admin/settings/wipe', {
            onSuccess: () => {
                closeWipeModal();
                setWipeConfirmation('');
                notifications.show({
                    title: 'Data wiped',
                    message:
                        'All comments, threads, and import mappings have been deleted.',
                    color: 'green',
                    icon: <IconCheck size={16} />,
                });
            },
            onFinish: () => {
                setIsWiping(false);
            },
        });
    };

    return (
        <AdminLayout>
            <Title order={2} mb="lg">
                Settings
            </Title>

            <form onSubmit={handleSubmit}>
                <Tabs
                    value={activeTab}
                    onChange={(value) => setActiveTab(value as SettingsTab)}
                >
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
                            value="auth"
                            leftSection={<IconBrandGithub size={16} />}
                        >
                            Authentication
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
                                    label="Admin Display Name"
                                    description="Name shown when posting comments as admin"
                                    value={data.admin_display_name}
                                    onChange={(e) =>
                                        setData(
                                            'admin_display_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <TextInput
                                    label="Admin Badge Label"
                                    description="Badge shown next to admin comments (e.g., Author, Admin, OP)"
                                    value={data.admin_badge_label}
                                    onChange={(e) =>
                                        setData(
                                            'admin_badge_label',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Author"
                                />
                                <TextInput
                                    label="Admin Email"
                                    description="Used for Gravatar and moderation notifications"
                                    type="email"
                                    value={data.admin_email}
                                    onChange={(e) =>
                                        setData('admin_email', e.target.value)
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
                                    description="Commenters can remain anonymous if disabled"
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
                                    description="Required for reply notifications"
                                    checked={data.require_email}
                                    onChange={(e) =>
                                        setData(
                                            'require_email',
                                            e.target.checked,
                                        )
                                    }
                                />
                                <Switch
                                    label="Enable upvotes"
                                    description="Allow users to upvote comments"
                                    checked={data.enable_upvotes}
                                    onChange={(e) => {
                                        const checked = e.target.checked;
                                        setData('enable_upvotes', checked);
                                        if (!checked) {
                                            setData('enable_downvotes', false);
                                        }
                                    }}
                                />
                                <Switch
                                    label="Enable downvotes"
                                    description="Allow users to downvote comments (requires upvotes)"
                                    checked={data.enable_downvotes}
                                    disabled={!data.enable_upvotes}
                                    onChange={(e) =>
                                        setData(
                                            'enable_downvotes',
                                            e.target.checked,
                                        )
                                    }
                                />
                                <NumberInput
                                    label="Max reply depth"
                                    description="Maximum nesting level for replies (0 = no replies allowed)"
                                    value={data.max_depth}
                                    onChange={(value) =>
                                        setData('max_depth', Number(value))
                                    }
                                    min={0}
                                    max={3}
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

                    <Tabs.Panel value="auth">
                        <Paper withBorder p="md" radius="md">
                            <Stack>
                                <Text size="sm" c="dimmed" mb="xs">
                                    Allow commenters to authenticate with their
                                    GitHub account. Create an OAuth App at{' '}
                                    <a
                                        href="https://github.com/settings/developers"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        github.com/settings/developers
                                    </a>{' '}
                                    and set the callback URL to:{' '}
                                    <code>
                                        {window.location.origin}
                                        /auth/github/callback
                                    </code>
                                </Text>
                                {settings.github_configured && (
                                    <Alert color="green">
                                        GitHub OAuth is configured. Leave secret
                                        empty to keep existing.
                                    </Alert>
                                )}
                                <TextInput
                                    label="GitHub Client ID"
                                    value={data.github_client_id}
                                    onChange={(e) =>
                                        setData(
                                            'github_client_id',
                                            e.target.value,
                                        )
                                    }
                                />
                                <PasswordInput
                                    label="GitHub Client Secret"
                                    value={data.github_client_secret}
                                    onChange={(e) =>
                                        setData(
                                            'github_client_secret',
                                            e.target.value,
                                        )
                                    }
                                    placeholder={
                                        settings.github_configured
                                            ? '••••••••'
                                            : ''
                                    }
                                />
                                <Switch
                                    label="Enable GitHub login for commenters"
                                    description={
                                        settings.github_configured ||
                                        data.github_client_id
                                            ? 'Allow commenters to authenticate via GitHub'
                                            : 'Enter GitHub Client ID and Secret above first'
                                    }
                                    checked={data.enable_github_login}
                                    disabled={
                                        !settings.github_configured &&
                                        !data.github_client_id
                                    }
                                    onChange={(e) =>
                                        setData(
                                            'enable_github_login',
                                            e.target.checked,
                                        )
                                    }
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
                                <ColorInput
                                    label="Accent Color"
                                    description="Primary color used for buttons, links, and highlights"
                                    value={data.accent_color}
                                    onChange={(value) =>
                                        setData('accent_color', value)
                                    }
                                    format="hex"
                                    swatches={[
                                        '#3b82f6',
                                        '#8b5cf6',
                                        '#ec4899',
                                        '#ef4444',
                                        '#f97316',
                                        '#eab308',
                                        '#22c55e',
                                        '#14b8a6',
                                        '#06b6d4',
                                        '#6366f1',
                                    ]}
                                />
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

            <Title order={3} mt="xl" mb="md" c="red">
                Danger Zone
            </Title>
            <Paper
                withBorder
                p="md"
                radius="md"
                style={{ borderColor: 'var(--mantine-color-red-6)' }}
            >
                <Group justify="space-between" align="center">
                    <div>
                        <Text fw={500}>Wipe all data</Text>
                        <Text size="sm" c="dimmed">
                            Delete all comments, threads, and import mappings.
                            Settings will be preserved.
                        </Text>
                    </div>
                    <Button
                        color="red"
                        variant="outline"
                        leftSection={<IconTrash size={16} />}
                        onClick={openWipeModal}
                    >
                        Wipe All Data
                    </Button>
                </Group>
            </Paper>

            <Modal
                opened={wipeModalOpened}
                onClose={closeWipeModal}
                title={
                    <Group gap="xs">
                        <IconAlertTriangle
                            size={20}
                            color="var(--mantine-color-red-6)"
                        />
                        <Text fw={600}>Confirm Data Wipe</Text>
                    </Group>
                }
            >
                <Stack>
                    <Alert color="red" icon={<IconAlertTriangle size={16} />}>
                        This action cannot be undone. All comments, threads, and
                        import mappings will be permanently deleted.
                    </Alert>
                    <TextInput
                        label='Type "DELETE" to confirm'
                        value={wipeConfirmation}
                        onChange={(e) => setWipeConfirmation(e.target.value)}
                        placeholder="DELETE"
                    />
                    <Group justify="flex-end">
                        <Button variant="default" onClick={closeWipeModal}>
                            Cancel
                        </Button>
                        <Button
                            color="red"
                            onClick={handleWipe}
                            loading={isWiping}
                            disabled={wipeConfirmation !== 'DELETE'}
                        >
                            Wipe All Data
                        </Button>
                    </Group>
                </Stack>
            </Modal>
        </AdminLayout>
    );
}
