import AdminLayout from '@/Layouts/AdminLayout';
import {
    Alert,
    Button,
    Card,
    Container,
    Group,
    Stack,
    Tabs,
    Text,
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
    IconX,
} from '@tabler/icons-react';
import { useState } from 'react';

type ImportType = 'isso' | 'json' | 'wordpress' | 'disqus';

export default function ImportIndex() {
    const [loading, setLoading] = useState<ImportType | null>(null);

    const handleImport = async (files: File[], type: ImportType) => {
        const file = files[0];
        if (!file) return;

        setLoading(type);
        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch(`/admin/import/${type}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                notifications.show({
                    title: 'Import successful',
                    message: data.message,
                    color: 'green',
                    icon: <IconCheck size={16} />,
                });
            } else {
                notifications.show({
                    title: 'Import failed',
                    message: data.message,
                    color: 'red',
                    icon: <IconX size={16} />,
                });
            }
        } catch {
            notifications.show({
                title: 'Import failed',
                message: 'An error occurred during import',
                color: 'red',
                icon: <IconX size={16} />,
            });
        } finally {
            setLoading(null);
        }
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
            a.download = `opaska-export-${new Date().toISOString().split('T')[0]}.json`;
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

    const importSources = [
        {
            type: 'isso' as ImportType,
            title: 'Import from Isso',
            description: 'Upload your isso SQLite database file (comments.db)',
            icon: IconDatabase,
            accept: ['application/x-sqlite3', 'application/octet-stream'],
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
            description: 'Upload a previously exported Opaska JSON file',
            icon: IconJson,
            accept: ['application/json'],
            dropText: 'Drag JSON file here or click to select',
            dropHint: 'Must be an Opaska export file (max 100MB)',
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
                        </Tabs.List>

                        {importSources.map((source) => (
                            <Tabs.Panel key={source.type} value={source.type}>
                                <Card withBorder mt="md">
                                    <Stack gap="md">
                                        <div>
                                            <Text fw={500}>{source.title}</Text>
                                            <Text size="sm" c="dimmed">
                                                {source.description}
                                            </Text>
                                        </div>

                                        <Dropzone
                                            onDrop={(files) =>
                                                handleImport(files, source.type)
                                            }
                                            loading={loading === source.type}
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
                                    </Stack>
                                </Card>
                            </Tabs.Panel>
                        ))}

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
                    </Tabs>
                </Stack>
            </Container>
        </AdminLayout>
    );
}
