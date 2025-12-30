import AdminLayout from '@/Layouts/AdminLayout';
import { router, useForm, usePage } from '@inertiajs/react';
import {
    Alert,
    Button,
    Card,
    Container,
    Group,
    Progress,
    Stack,
    Tabs,
    Text,
    TextInput,
    Title,
} from '@mantine/core';
import { Dropzone } from '@mantine/dropzone';
import { notifications } from '@mantine/notifications';
import {
    IconBrandWordpress,
    IconCheck,
    IconDatabase,
    IconDownload,
    IconJson,
    IconMessageCircle,
    IconUpload,
    IconUserCheck,
    IconX,
} from '@tabler/icons-react';
import { useCallback, useEffect, useState } from 'react';

type ImportType = 'isso' | 'json' | 'wordpress' | 'disqus';

type PageProps = {
    flash: {
        success?: string;
        error?: string;
    };
};

export default function ImportIndex() {
    const { flash } = usePage<PageProps>().props;

    const issoForm = useForm<{ file: File | null }>({ file: null });
    const jsonForm = useForm<{ file: File | null }>({ file: null });
    const wordpressForm = useForm<{ file: File | null }>({ file: null });
    const disqusForm = useForm<{ file: File | null }>({ file: null });

    // Claim admin state
    const [claimEmail, setClaimEmail] = useState('');
    const [claimAuthor, setClaimAuthor] = useState('');
    const [claimPreview, setClaimPreview] = useState<{
        count: number;
        comments: Array<{
            id: number;
            author: string;
            body_excerpt: string;
            thread_uri: string;
            created_at: string;
        }>;
    } | null>(null);
    const [isClaimLoading, setIsClaimLoading] = useState(false);

    const forms = {
        isso: issoForm,
        json: jsonForm,
        wordpress: wordpressForm,
        disqus: disqusForm,
    };

    useEffect(() => {
        if (flash.success) {
            notifications.show({
                title: 'Import successful',
                message: flash.success,
                color: 'green',
                icon: <IconCheck size={16} />,
            });
        }
    }, [flash.success]);

    const handleImport = (files: File[], type: ImportType) => {
        const file = files[0];
        if (!file) return;

        const form = forms[type];
        form.setData({ file });

        router.post(
            `/admin/import/${type}`,
            { file },
            {
                forceFormData: true,
                onError: (errors) => {
                    const message =
                        errors.file || 'An error occurred during import';
                    notifications.show({
                        title: 'Import failed',
                        message,
                        color: 'red',
                        icon: <IconX size={16} />,
                    });
                },
            },
        );
    };

    const handleExport = async () => {
        try {
            const response = await fetch('/admin/export');
            const data = await response.json();

            const blob = new Blob([JSON.stringify(data, null, 2)], {
                type: 'application/json',
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `marge-export-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            notifications.show({
                title: 'Export successful',
                message: 'Your data has been exported',
                color: 'green',
                icon: <IconCheck size={16} />,
            });
        } catch {
            notifications.show({
                title: 'Export failed',
                message: 'An error occurred during export',
                color: 'red',
                icon: <IconX size={16} />,
            });
        }
    };

    const fetchClaimPreview = useCallback(
        async (email: string, author: string) => {
            if (!email && !author) {
                setClaimPreview(null);
                return;
            }
            setIsClaimLoading(true);
            try {
                const params = new URLSearchParams();
                if (email) params.set('email', email);
                if (author) params.set('author', author);
                const response = await fetch(
                    `/admin/settings/claim-admin/preview?${params}`,
                );
                const data = await response.json();
                setClaimPreview(data);
            } finally {
                setIsClaimLoading(false);
            }
        },
        [],
    );

    // Debounced preview fetch
    useEffect(() => {
        const timer = setTimeout(() => {
            fetchClaimPreview(claimEmail, claimAuthor);
        }, 300);
        return () => clearTimeout(timer);
    }, [claimEmail, claimAuthor, fetchClaimPreview]);

    const handleClaim = () => {
        setIsClaimLoading(true);
        router.post(
            '/admin/settings/claim-admin',
            { email: claimEmail, author: claimAuthor },
            {
                onSuccess: () => {
                    setClaimEmail('');
                    setClaimAuthor('');
                    setClaimPreview(null);
                    notifications.show({
                        title: 'Comments claimed',
                        message: 'Matching comments have been marked as admin.',
                        color: 'green',
                        icon: <IconCheck size={16} />,
                    });
                },
                onFinish: () => {
                    setIsClaimLoading(false);
                },
            },
        );
    };

    const importSources = [
        {
            type: 'isso' as ImportType,
            title: 'Import from Isso',
            description: 'Upload your isso SQLite database file (comments.db)',
            icon: IconDatabase,
            accept: undefined,
            dropText: 'Drag isso database here or click to select',
            dropHint: 'File should be comments.db (max 100MB)',
        },
        {
            type: 'wordpress' as ImportType,
            title: 'Import from WordPress',
            description: 'Upload a WordPress WXR export file (.xml)',
            icon: IconBrandWordpress,
            accept: ['application/xml', 'text/xml'],
            dropText: 'Drag WordPress export here or click to select',
            dropHint: 'WXR export file from WordPress (max 100MB)',
        },
        {
            type: 'disqus' as ImportType,
            title: 'Import from Disqus',
            description: 'Upload a Disqus XML export file',
            icon: IconMessageCircle,
            accept: ['application/xml', 'text/xml'],
            dropText: 'Drag Disqus export here or click to select',
            dropHint: 'XML export from Disqus (max 100MB)',
        },
        {
            type: 'json' as ImportType,
            title: 'Import from JSON',
            description: 'Upload a previously exported Marge JSON file',
            icon: IconJson,
            accept: ['application/json'],
            dropText: 'Drag JSON file here or click to select',
            dropHint: 'Must be an Marge export file (max 100MB)',
        },
    ];

    return (
        <AdminLayout>
            <Container size="lg">
                <Stack gap="lg">
                    <div>
                        <Title order={2}>Import & Export</Title>
                        <Text c="dimmed">
                            Import comments from other systems or export your
                            data
                        </Text>
                    </div>

                    <Alert color="yellow" title="Backup recommended">
                        Before importing, it&apos;s recommended to export your
                        current data as a backup.
                    </Alert>

                    <Tabs defaultValue="isso">
                        <Tabs.List>
                            {importSources.map((source) => (
                                <Tabs.Tab
                                    key={source.type}
                                    value={source.type}
                                    leftSection={<source.icon size={16} />}
                                >
                                    {source.title.replace('Import from ', '')}
                                </Tabs.Tab>
                            ))}
                            <Tabs.Tab
                                value="export"
                                leftSection={<IconDownload size={16} />}
                            >
                                Export
                            </Tabs.Tab>
                            <Tabs.Tab
                                value="claim"
                                leftSection={<IconUserCheck size={16} />}
                            >
                                Claim
                            </Tabs.Tab>
                        </Tabs.List>

                        {importSources.map((source) => {
                            const form = forms[source.type];
                            return (
                                <Tabs.Panel
                                    key={source.type}
                                    value={source.type}
                                >
                                    <Card withBorder mt="md">
                                        <Stack gap="md">
                                            <div>
                                                <Text fw={500}>
                                                    {source.title}
                                                </Text>
                                                <Text size="sm" c="dimmed">
                                                    {source.description}
                                                </Text>
                                            </div>

                                            <Dropzone
                                                onDrop={(files) =>
                                                    handleImport(
                                                        files,
                                                        source.type,
                                                    )
                                                }
                                                loading={form.processing}
                                                accept={source.accept}
                                                maxSize={100 * 1024 * 1024}
                                                maxFiles={1}
                                            >
                                                <Group
                                                    justify="center"
                                                    gap="xl"
                                                    mih={120}
                                                    style={{
                                                        pointerEvents: 'none',
                                                    }}
                                                >
                                                    <Dropzone.Accept>
                                                        <IconUpload
                                                            size={40}
                                                            stroke={1.5}
                                                        />
                                                    </Dropzone.Accept>
                                                    <Dropzone.Reject>
                                                        <IconX
                                                            size={40}
                                                            stroke={1.5}
                                                        />
                                                    </Dropzone.Reject>
                                                    <Dropzone.Idle>
                                                        <source.icon
                                                            size={40}
                                                            stroke={1.5}
                                                        />
                                                    </Dropzone.Idle>

                                                    <div>
                                                        <Text size="lg" inline>
                                                            {source.dropText}
                                                        </Text>
                                                        <Text
                                                            size="sm"
                                                            c="dimmed"
                                                            inline
                                                            mt={7}
                                                        >
                                                            {source.dropHint}
                                                        </Text>
                                                    </div>
                                                </Group>
                                            </Dropzone>

                                            {form.progress && (
                                                <Progress
                                                    value={
                                                        form.progress
                                                            .percentage ?? 0
                                                    }
                                                    animated
                                                />
                                            )}
                                        </Stack>
                                    </Card>
                                </Tabs.Panel>
                            );
                        })}

                        <Tabs.Panel value="export">
                            <Card withBorder mt="md">
                                <Group justify="space-between">
                                    <div>
                                        <Text fw={500}>Export Data</Text>
                                        <Text size="sm" c="dimmed">
                                            Download all threads and comments as
                                            JSON
                                        </Text>
                                    </div>
                                    <Button
                                        leftSection={<IconDownload size={16} />}
                                        onClick={handleExport}
                                    >
                                        Export JSON
                                    </Button>
                                </Group>
                            </Card>
                        </Tabs.Panel>

                        <Tabs.Panel value="claim">
                            <Card withBorder mt="md">
                                <Stack gap="md">
                                    <div>
                                        <Text fw={500}>
                                            Claim Admin Comments
                                        </Text>
                                        <Text size="sm" c="dimmed">
                                            Mark imported comments as admin
                                            based on email or author name.
                                            Useful for claiming your old
                                            comments.
                                        </Text>
                                    </div>
                                    <Group align="flex-end" gap="sm">
                                        <TextInput
                                            label="Email"
                                            placeholder="your@email.com"
                                            value={claimEmail}
                                            onChange={(e) =>
                                                setClaimEmail(e.target.value)
                                            }
                                            autoComplete="off"
                                            data-1p-ignore
                                            style={{ flex: 1 }}
                                        />
                                        <TextInput
                                            label="Author"
                                            placeholder="Your Name"
                                            value={claimAuthor}
                                            onChange={(e) =>
                                                setClaimAuthor(e.target.value)
                                            }
                                            autoComplete="off"
                                            data-1p-ignore
                                            style={{ flex: 1 }}
                                        />
                                    </Group>
                                    {claimPreview !== null &&
                                        claimPreview.count > 0 && (
                                            <Stack gap="sm">
                                                <Group
                                                    justify="space-between"
                                                    align="center"
                                                >
                                                    <Text size="sm">
                                                        Found{' '}
                                                        <strong>
                                                            {claimPreview.count}{' '}
                                                            comments
                                                        </strong>
                                                        {claimPreview.count >
                                                            10 &&
                                                            ' (showing 10 most recent)'}
                                                    </Text>
                                                    <Button
                                                        size="sm"
                                                        leftSection={
                                                            <IconUserCheck
                                                                size={16}
                                                            />
                                                        }
                                                        onClick={handleClaim}
                                                        loading={isClaimLoading}
                                                    >
                                                        Claim as admin
                                                    </Button>
                                                </Group>
                                                <Stack gap="xs">
                                                    {claimPreview.comments.map(
                                                        (comment) => (
                                                            <Card
                                                                key={comment.id}
                                                                padding="xs"
                                                                withBorder
                                                            >
                                                                <Group
                                                                    justify="space-between"
                                                                    wrap="nowrap"
                                                                >
                                                                    <div
                                                                        style={{
                                                                            minWidth: 0,
                                                                            flex: 1,
                                                                        }}
                                                                    >
                                                                        <Text
                                                                            size="sm"
                                                                            truncate
                                                                        >
                                                                            {
                                                                                comment.body_excerpt
                                                                            }
                                                                        </Text>
                                                                        <Text
                                                                            size="xs"
                                                                            c="dimmed"
                                                                        >
                                                                            {
                                                                                comment.thread_uri
                                                                            }
                                                                        </Text>
                                                                    </div>
                                                                    <Text
                                                                        size="xs"
                                                                        c="dimmed"
                                                                        style={{
                                                                            whiteSpace:
                                                                                'nowrap',
                                                                        }}
                                                                    >
                                                                        {
                                                                            comment.created_at
                                                                        }
                                                                    </Text>
                                                                </Group>
                                                            </Card>
                                                        ),
                                                    )}
                                                </Stack>
                                            </Stack>
                                        )}
                                    {claimPreview !== null &&
                                        claimPreview.count === 0 &&
                                        (claimEmail || claimAuthor) && (
                                            <Text size="sm" c="dimmed">
                                                No matching comments found.
                                            </Text>
                                        )}
                                    {claimPreview === null &&
                                        !claimEmail &&
                                        !claimAuthor && (
                                            <Text size="sm" c="dimmed">
                                                Enter an email or author name to
                                                find comments.
                                            </Text>
                                        )}
                                </Stack>
                            </Card>
                        </Tabs.Panel>
                    </Tabs>
                </Stack>
            </Container>
        </AdminLayout>
    );
}
