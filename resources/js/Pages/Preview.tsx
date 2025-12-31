import AdminLayout from '@/Layouts/AdminLayout';
import {
    Box,
    Container,
    Group,
    Paper,
    SegmentedControl,
    Select,
    Stack,
    Text,
    Title,
    useComputedColorScheme,
} from '@mantine/core';
import { useEffect, useRef, useState } from 'react';

declare global {
    interface Window {
        Marge?: unknown;
    }
}

interface Thread {
    uri: string;
    title: string | null;
    comments_count: number;
}

interface PreviewProps {
    appUrl: string;
    threads: Thread[];
}

export default function Preview({ appUrl, threads }: PreviewProps) {
    const [viewMode, setViewMode] = useState('admin');
    const [selectedThread, setSelectedThread] = useState<string | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);
    const theme = useComputedColorScheme('light');

    const threadOptions = [
        { value: '', label: 'New thread (preview-page)' },
        ...threads.map((t) => ({
            value: t.uri,
            label: `${t.title || t.uri} (${t.comments_count} comments)`,
        })),
    ];

    useEffect(() => {
        if (!containerRef.current) return;

        // Clear previous widget
        const container = containerRef.current;
        const threadUri = selectedThread || '/preview-page';
        container.innerHTML = `<div id="marge-thread" data-uri="${threadUri}"></div>`;

        // Remove any existing script
        const existingScript = document.querySelector(
            'script[data-marge-preview]',
        );
        if (existingScript) {
            existingScript.remove();
        }

        // Clean up Marge global state if it exists
        if (typeof window !== 'undefined' && window.Marge) {
            window.Marge = undefined;
        }

        // Create and append new script (with cache buster to force reload)
        const script = document.createElement('script');
        script.src = `${appUrl}/embed/embed.js?t=${Date.now()}`;
        script.setAttribute('data-marge', appUrl);
        script.setAttribute('data-marge-theme', theme);
        script.setAttribute('data-marge-preview', 'true');
        if (viewMode === 'guest') {
            script.setAttribute('data-marge-guest', 'true');
        }
        script.async = true;
        container.appendChild(script);

        return () => {
            // Cleanup on unmount
            const scriptToRemove = document.querySelector(
                'script[data-marge-preview]',
            );
            if (scriptToRemove) {
                scriptToRemove.remove();
            }
        };
    }, [appUrl, theme, viewMode, selectedThread]);

    return (
        <AdminLayout>
            <Container size="lg">
                <Stack gap="lg">
                    <div>
                        <Title order={2}>Widget Preview</Title>
                        <Text c="dimmed">
                            See how the comment widget looks on your site
                        </Text>
                    </div>

                    <Paper p="md" withBorder>
                        <Group gap="lg" align="flex-end" wrap="wrap">
                            <div>
                                <Text size="sm" fw={500} mb={4}>
                                    View as
                                </Text>
                                <SegmentedControl
                                    size="xs"
                                    value={viewMode}
                                    onChange={setViewMode}
                                    data={[
                                        { label: 'Admin', value: 'admin' },
                                        { label: 'Guest', value: 'guest' },
                                    ]}
                                />
                            </div>
                            <Select
                                label="Thread"
                                size="xs"
                                value={selectedThread}
                                onChange={setSelectedThread}
                                data={threadOptions}
                                placeholder="Select a thread"
                                searchable
                                clearable
                                style={{ flex: 1, minWidth: 250 }}
                            />
                        </Group>
                    </Paper>

                    {/* Mock blog post */}
                    <Paper
                        p="xl"
                        withBorder
                        style={{
                            backgroundColor:
                                theme === 'dark' ? '#1a1b1e' : '#ffffff',
                        }}
                    >
                        <Box maw={720} mx="auto">
                            <article>
                                <Title
                                    order={1}
                                    mb="md"
                                    style={{
                                        color:
                                            theme === 'dark'
                                                ? '#c1c2c5'
                                                : '#212529',
                                    }}
                                >
                                    Welcome to My Blog
                                </Title>
                                <Text
                                    mb="xl"
                                    style={{
                                        color:
                                            theme === 'dark'
                                                ? '#909296'
                                                : '#495057',
                                    }}
                                >
                                    This is a sample blog post to demonstrate
                                    how the Marge comment widget integrates with
                                    your content. The widget below allows
                                    visitors to leave comments, reply to others,
                                    and engage with your content.
                                </Text>
                                <Text
                                    mb="xl"
                                    style={{
                                        color:
                                            theme === 'dark'
                                                ? '#909296'
                                                : '#495057',
                                    }}
                                >
                                    Try posting a comment below to see how it
                                    works! You can use Markdown for formatting,
                                    including <strong>bold</strong>,{' '}
                                    <em>italic</em>, and code blocks.
                                </Text>
                            </article>

                            {/* Comment widget container */}
                            <Box
                                mt="xl"
                                pt="xl"
                                style={{
                                    borderTop: `1px solid ${theme === 'dark' ? '#373a40' : '#dee2e6'}`,
                                }}
                            >
                                <div ref={containerRef}>
                                    <div id="marge-thread" />
                                </div>
                            </Box>
                        </Box>
                    </Paper>
                </Stack>
            </Container>
        </AdminLayout>
    );
}
