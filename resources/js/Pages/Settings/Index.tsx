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
    IconBrandTelegram,
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
        allowed_origins: string;
        custom_css: string;
        accent_color: string;
        hide_branding: boolean;
        enable_upvotes: boolean;
        enable_downvotes: boolean;
        enable_github_login: boolean;
        github_client_id: string | null;
        github_client_secret: string | null;
        enable_telegram: boolean;
        telegram_chat_id: string | null;
        telegram_bot_token: string | null;
        telegram_notify_upvotes: boolean;
        telegram_webhook: {
            configured: boolean;
            url: string | null;
            error: string | null;
        };
        enable_email: boolean;
        smtp_host: string | null;
        smtp_port: string;
        smtp_username: string | null;
        smtp_password: string | null;
        smtp_from_address: string | null;
        smtp_from_name: string | null;
        smtp_encryption: string;
    };
}

type SettingsTab =
    | 'general'
    | 'moderation'
    | 'auth'
    | 'telegram'
    | 'email'
    | 'appearance'
    | 'danger';

export default function SettingsIndex({ settings }: SettingsIndexProps) {
    const [activeTab, setActiveTab] = useUrlState<SettingsTab>(
        'tab',
        'general',
    );
    const [wipeModalOpened, { open: openWipeModal, close: closeWipeModal }] =
        useDisclosure(false);
    const [wipeConfirmation, setWipeConfirmation] = useState('');
    const [isWiping, setIsWiping] = useState(false);
    const [isTelegramAction, setIsTelegramAction] = useState(false);
    const [isEmailAction, setIsEmailAction] = useState(false);

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
        allowed_origins: settings.allowed_origins,
        custom_css: settings.custom_css,
        accent_color: settings.accent_color,
        hide_branding: settings.hide_branding,
        enable_upvotes: settings.enable_upvotes,
        enable_downvotes: settings.enable_downvotes,
        enable_github_login: settings.enable_github_login,
        github_client_id: settings.github_client_id || '',
        github_client_secret: settings.github_client_secret || '',
        enable_telegram: settings.enable_telegram,
        telegram_chat_id: settings.telegram_chat_id || '',
        telegram_bot_token: settings.telegram_bot_token || '',
        telegram_notify_upvotes: settings.telegram_notify_upvotes,
        enable_email: settings.enable_email,
        smtp_host: settings.smtp_host || '',
        smtp_port: settings.smtp_port || '587',
        smtp_username: settings.smtp_username || '',
        smtp_password: settings.smtp_password || '',
        smtp_from_address: settings.smtp_from_address || '',
        smtp_from_name: settings.smtp_from_name || '',
        smtp_encryption: settings.smtp_encryption || 'tls',
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

    const showTelegramResult = (page: { props: Record<string, unknown> }) => {
        const flash = page.props.flash as
            | { success?: string; error?: string }
            | undefined;
        if (flash?.success) {
            notifications.show({
                title: 'Telegram',
                message: flash.success,
                color: 'green',
                icon: <IconCheck size={16} />,
            });
        }
        if (flash?.error) {
            notifications.show({
                title: 'Telegram',
                message: flash.error,
                color: 'red',
            });
        }
    };

    const handleTelegramSetupWebhook = () => {
        setIsTelegramAction(true);
        router.post(
            '/admin/settings/telegram/setup-webhook',
            {},
            {
                preserveScroll: true,
                onSuccess: showTelegramResult,
                onFinish: () => setIsTelegramAction(false),
            },
        );
    };

    const handleTelegramRemoveWebhook = () => {
        setIsTelegramAction(true);
        router.post(
            '/admin/settings/telegram/remove-webhook',
            {},
            {
                preserveScroll: true,
                onSuccess: showTelegramResult,
                onFinish: () => setIsTelegramAction(false),
            },
        );
    };

    const handleTelegramTest = () => {
        setIsTelegramAction(true);
        router.post(
            '/admin/settings/telegram/test',
            {},
            {
                preserveScroll: true,
                onSuccess: showTelegramResult,
                onFinish: () => setIsTelegramAction(false),
            },
        );
    };

    const showEmailResult = (page: { props: Record<string, unknown> }) => {
        const flash = page.props.flash as
            | { success?: string; error?: string }
            | undefined;
        if (flash?.success) {
            notifications.show({
                title: 'Email',
                message: flash.success,
                color: 'green',
                icon: <IconCheck size={16} />,
            });
        }
        if (flash?.error) {
            notifications.show({
                title: 'Email',
                message: flash.error,
                color: 'red',
            });
        }
    };

    const handleEmailTest = () => {
        setIsEmailAction(true);
        router.post(
            '/admin/settings/email/test',
            {},
            {
                preserveScroll: true,
                onSuccess: showEmailResult,
                onFinish: () => setIsEmailAction(false),
            },
        );
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
                            value="appearance"
                            leftSection={<IconPalette size={16} />}
                        >
                            Appearance
                        </Tabs.Tab>
                        <Tabs.Tab
                            value="email"
                            leftSection={<IconMail size={16} />}
                        >
                            Email
                        </Tabs.Tab>
                        <Tabs.Tab
                            value="telegram"
                            leftSection={<IconBrandTelegram size={16} />}
                        >
                            Telegram
                        </Tabs.Tab>
                        <Tabs.Tab
                            value="auth"
                            leftSection={<IconBrandGithub size={16} />}
                        >
                            GitHub
                        </Tabs.Tab>
                        <Tabs.Tab
                            value="danger"
                            leftSection={<IconAlertTriangle size={16} />}
                            color="red"
                        >
                            Danger Zone
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
                                    description="Comma-separated list of domains allowed to embed comments. Defaults to Site URL if empty."
                                    value={data.allowed_origins}
                                    onChange={(e) =>
                                        setData(
                                            'allowed_origins',
                                            e.target.value,
                                        )
                                    }
                                    error={
                                        data.allowed_origins.trim() === '*'
                                            ? 'Warning: Wildcard (*) allows any site to embed comments. Authentication cookies will be disabled for security.'
                                            : undefined
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
                            </Stack>
                        </Paper>
                    </Tabs.Panel>

                    <Tabs.Panel value="telegram">
                        <Paper withBorder p="md" radius="md">
                            <Stack>
                                <Text size="sm" c="dimmed">
                                    Receive notifications via Telegram when new
                                    comments are posted. Create a bot at{' '}
                                    <a
                                        href="https://t.me/BotFather"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        @BotFather
                                    </a>{' '}
                                    and get your chat ID by messaging{' '}
                                    <a
                                        href="https://t.me/userinfobot"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        @userinfobot
                                    </a>
                                    .
                                </Text>
                                <PasswordInput
                                    label="Bot Token"
                                    description="Token from @BotFather"
                                    value={data.telegram_bot_token}
                                    onChange={(e) =>
                                        setData(
                                            'telegram_bot_token',
                                            e.target.value,
                                        )
                                    }
                                />
                                <TextInput
                                    label="Chat ID"
                                    description="Your Telegram user or group chat ID"
                                    value={data.telegram_chat_id}
                                    onChange={(e) =>
                                        setData(
                                            'telegram_chat_id',
                                            e.target.value,
                                        )
                                    }
                                />
                                <Switch
                                    label="Enable Telegram notifications"
                                    description={
                                        settings.telegram_bot_token ||
                                        data.telegram_bot_token
                                            ? 'Send notifications to Telegram'
                                            : 'Enter Bot Token above first'
                                    }
                                    checked={data.enable_telegram}
                                    disabled={
                                        !settings.telegram_bot_token &&
                                        !data.telegram_bot_token
                                    }
                                    onChange={(e) =>
                                        setData(
                                            'enable_telegram',
                                            e.target.checked,
                                        )
                                    }
                                />
                                <Switch
                                    label="Notify on upvotes"
                                    description="Also send notifications when comments receive upvotes"
                                    checked={data.telegram_notify_upvotes}
                                    disabled={!data.enable_telegram}
                                    onChange={(e) =>
                                        setData(
                                            'telegram_notify_upvotes',
                                            e.target.checked,
                                        )
                                    }
                                />
                                {data.telegram_bot_token &&
                                    !settings.telegram_bot_token && (
                                        <Alert color="yellow">
                                            Save settings first to use
                                            Test/Webhook buttons.
                                        </Alert>
                                    )}
                                <Group mt="md">
                                    <Button
                                        variant="light"
                                        onClick={handleTelegramTest}
                                        loading={isTelegramAction}
                                        disabled={!settings.telegram_bot_token}
                                    >
                                        Test Connection
                                    </Button>
                                    {!settings.telegram_webhook.configured ? (
                                        <Button
                                            variant="light"
                                            onClick={handleTelegramSetupWebhook}
                                            loading={isTelegramAction}
                                            disabled={
                                                !settings.telegram_bot_token
                                            }
                                        >
                                            Setup Webhook
                                        </Button>
                                    ) : (
                                        <Button
                                            variant="light"
                                            color="red"
                                            onClick={
                                                handleTelegramRemoveWebhook
                                            }
                                            loading={isTelegramAction}
                                        >
                                            Remove Webhook
                                        </Button>
                                    )}
                                </Group>
                                {settings.telegram_webhook.configured && (
                                    <Alert
                                        color="blue"
                                        mt="sm"
                                        title="Webhook active"
                                    >
                                        <Text size="sm" mb="xs">
                                            <code>
                                                {settings.telegram_webhook.url}
                                            </code>
                                        </Text>
                                        <Text size="sm" c="dimmed">
                                            <b>Reply</b> to post as admin •{' '}
                                            <b>React</b>: 👌 approve, 💩 delete,
                                            👍 upvote
                                            {settings.enable_downvotes &&
                                                ', 👎 downvote'}
                                        </Text>
                                    </Alert>
                                )}
                                {settings.telegram_webhook.error && (
                                    <Alert color="red" mt="sm">
                                        Webhook error:{' '}
                                        {settings.telegram_webhook.error}
                                    </Alert>
                                )}
                            </Stack>
                        </Paper>
                    </Tabs.Panel>

                    <Tabs.Panel value="email">
                        <Paper withBorder p="md" radius="md">
                            <Stack>
                                <Text size="sm" c="dimmed">
                                    Configure SMTP settings to send email
                                    notifications when someone replies to a
                                    comment. Emails are sent to users who
                                    subscribe to reply notifications.
                                </Text>
                                <TextInput
                                    label="SMTP Host"
                                    description="SMTP server hostname (e.g., smtp.mailgun.org)"
                                    value={data.smtp_host}
                                    onChange={(e) =>
                                        setData('smtp_host', e.target.value)
                                    }
                                />
                                <NumberInput
                                    label="SMTP Port"
                                    description="Usually 587 for TLS, 465 for SSL, or 25 for unencrypted"
                                    value={Number(data.smtp_port)}
                                    onChange={(value) =>
                                        setData(
                                            'smtp_port',
                                            String(value || 587),
                                        )
                                    }
                                    min={1}
                                    max={65535}
                                />
                                <Select
                                    label="Encryption"
                                    description="Connection security method"
                                    data={[
                                        {
                                            value: 'tls',
                                            label: 'STARTTLS (port 587)',
                                        },
                                        {
                                            value: 'ssl',
                                            label: 'SSL/TLS (port 465)',
                                        },
                                        {
                                            value: 'none',
                                            label: 'None (port 25)',
                                        },
                                    ]}
                                    value={data.smtp_encryption}
                                    onChange={(value) =>
                                        setData(
                                            'smtp_encryption',
                                            value || 'tls',
                                        )
                                    }
                                />
                                <TextInput
                                    label="SMTP Username"
                                    description="Authentication username"
                                    value={data.smtp_username}
                                    onChange={(e) =>
                                        setData('smtp_username', e.target.value)
                                    }
                                />
                                <PasswordInput
                                    label="SMTP Password"
                                    description="Authentication password"
                                    value={data.smtp_password}
                                    onChange={(e) =>
                                        setData('smtp_password', e.target.value)
                                    }
                                />
                                <TextInput
                                    label="From Address"
                                    description="Email address that notifications are sent from"
                                    type="email"
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
                                    description="Display name for the sender (defaults to site name)"
                                    value={data.smtp_from_name}
                                    onChange={(e) =>
                                        setData(
                                            'smtp_from_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <Switch
                                    label="Enable email notifications"
                                    description={
                                        data.smtp_host
                                            ? 'Send reply notifications via email'
                                            : 'Enter SMTP settings above first'
                                    }
                                    checked={data.enable_email}
                                    disabled={!data.smtp_host}
                                    onChange={(e) =>
                                        setData(
                                            'enable_email',
                                            e.target.checked,
                                        )
                                    }
                                />
                                {data.smtp_host && !settings.smtp_host && (
                                    <Alert color="yellow">
                                        Save settings first to use the Test
                                        button.
                                    </Alert>
                                )}
                                <Group mt="md">
                                    <Button
                                        variant="light"
                                        onClick={handleEmailTest}
                                        loading={isEmailAction}
                                        disabled={!settings.smtp_host}
                                    >
                                        Send Test Email
                                    </Button>
                                </Group>
                                <Text size="xs" c="dimmed">
                                    Test email will be sent to the admin email
                                    address configured in General settings.
                                </Text>
                            </Stack>
                        </Paper>
                    </Tabs.Panel>

                    <Tabs.Panel value="auth">
                        <Paper withBorder p="md" radius="md">
                            <Stack>
                                <Text size="sm" c="dimmed">
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
                                <TextInput
                                    label="GitHub Client ID"
                                    value={data.github_client_id}
                                    onChange={(e) => {
                                        const value = e.target.value;
                                        setData((prev) => ({
                                            ...prev,
                                            github_client_id: value,
                                            enable_github_login:
                                                value &&
                                                prev.github_client_secret
                                                    ? prev.enable_github_login
                                                    : false,
                                        }));
                                    }}
                                />
                                <PasswordInput
                                    label="GitHub Client Secret"
                                    value={data.github_client_secret}
                                    onChange={(e) => {
                                        const value = e.target.value;
                                        setData((prev) => ({
                                            ...prev,
                                            github_client_secret: value,
                                            enable_github_login:
                                                prev.github_client_id && value
                                                    ? prev.enable_github_login
                                                    : false,
                                        }));
                                    }}
                                />
                                <Switch
                                    label="Enable GitHub login for commenters"
                                    description={
                                        data.github_client_id &&
                                        data.github_client_secret
                                            ? 'Allow commenters to authenticate via GitHub'
                                            : 'Enter GitHub Client ID and Secret above first'
                                    }
                                    checked={data.enable_github_login}
                                    disabled={
                                        !data.github_client_id ||
                                        !data.github_client_secret
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
                                <Switch
                                    label="Hide branding"
                                    description="Hide the 'Powered by Bulla' footer in the embed"
                                    checked={data.hide_branding}
                                    onChange={(e) =>
                                        setData(
                                            'hide_branding',
                                            e.target.checked,
                                        )
                                    }
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

                    <Tabs.Panel value="danger">
                        <Paper
                            withBorder
                            p="md"
                            radius="md"
                            style={{
                                borderColor: 'var(--mantine-color-red-6)',
                            }}
                        >
                            <Group justify="space-between" align="center">
                                <div>
                                    <Text fw={500}>Wipe all data</Text>
                                    <Text size="sm" c="dimmed">
                                        Delete all comments, threads, and import
                                        mappings. Settings will be preserved.
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
                    </Tabs.Panel>
                </Tabs>

                {activeTab !== 'danger' && (
                    <Group justify="flex-end" mt="lg">
                        <Button type="submit" loading={processing}>
                            Save Settings
                        </Button>
                    </Group>
                )}
            </form>

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
