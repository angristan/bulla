import { Link, router } from '@inertiajs/react';
import {
    Anchor,
    Avatar,
    Badge,
    Button,
    Code,
    Divider,
    Group,
    Paper,
    Stack,
    Text,
    Title,
    TypographyStylesProvider,
} from '@mantine/core';
import {
    IconArrowLeft,
    IconCheck,
    IconExternalLink,
    IconMail,
    IconThumbUp,
    IconTrash,
    IconWorld,
    IconX,
} from '@tabler/icons-react';
import AdminLayout from '@/Layouts/AdminLayout';

interface CommentShowProps {
    comment: {
        id: number;
        author: string | null;
        email: string | null;
        website: string | null;
        avatar: string;
        body_markdown: string;
        body_html: string;
        status: string;
        is_admin: boolean;
        upvotes: number;
        remote_addr: string | null;
        user_agent: string | null;
        created_at: string;
        thread: {
            id: number;
            uri: string;
            title: string | null;
            url: string | null;
        };
        parent: {
            id: number;
            author: string | null;
        } | null;
        replies_count: number;
    };
}

const statusColors: Record<string, string> = {
    pending: 'yellow',
    approved: 'green',
    spam: 'red',
    deleted: 'gray',
};

export default function CommentShow({ comment }: CommentShowProps) {
    const handleAction = (action: string) => {
        router.post(
            `/admin/comments/${comment.id}/${action}`,
            {},
            {
                preserveState: true,
            },
        );
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this comment?')) {
            router.delete(`/admin/comments/${comment.id}`);
        }
    };

    return (
        <AdminLayout>
            <Group mb="lg">
                <Button
                    component={Link}
                    href="/admin/comments"
                    variant="subtle"
                    leftSection={<IconArrowLeft size={16} />}
                >
                    Back to Comments
                </Button>
            </Group>

            <Paper withBorder p="lg" radius="md" mb="lg">
                <Group justify="space-between" mb="md">
                    <Group>
                        <Avatar src={comment.avatar} size="lg" radius="xl" />
                        <div>
                            <Group gap="xs">
                                <Text fw={600}>
                                    {comment.author || 'Anonymous'}
                                </Text>
                                {comment.is_admin && (
                                    <Badge size="xs" color="blue">
                                        Admin
                                    </Badge>
                                )}
                            </Group>
                            <Text size="sm" c="dimmed">
                                {new Date(comment.created_at).toLocaleString()}
                            </Text>
                        </div>
                    </Group>
                    <Badge color={statusColors[comment.status]} size="lg">
                        {comment.status}
                    </Badge>
                </Group>

                <Divider my="md" />

                <TypographyStylesProvider>
                    <div
                        dangerouslySetInnerHTML={{ __html: comment.body_html }}
                    />
                </TypographyStylesProvider>

                <Divider my="md" />

                <Group justify="space-between">
                    <Group gap="lg">
                        {comment.email && (
                            <Group gap="xs">
                                <IconMail size={16} color="gray" />
                                <Text size="sm" c="dimmed">
                                    {comment.email}
                                </Text>
                            </Group>
                        )}
                        {comment.website && (
                            <Group gap="xs">
                                <IconWorld size={16} color="gray" />
                                <Anchor
                                    href={comment.website}
                                    target="_blank"
                                    size="sm"
                                >
                                    {comment.website}
                                </Anchor>
                            </Group>
                        )}
                        <Group gap="xs">
                            <IconThumbUp size={16} color="gray" />
                            <Text size="sm" c="dimmed">
                                {comment.upvotes} upvotes
                            </Text>
                        </Group>
                    </Group>
                    <Group>
                        {comment.status !== 'approved' && (
                            <Button
                                variant="light"
                                color="green"
                                leftSection={<IconCheck size={16} />}
                                onClick={() => handleAction('approve')}
                            >
                                Approve
                            </Button>
                        )}
                        {comment.status !== 'spam' && (
                            <Button
                                variant="light"
                                color="yellow"
                                leftSection={<IconX size={16} />}
                                onClick={() => handleAction('spam')}
                            >
                                Mark Spam
                            </Button>
                        )}
                        <Button
                            variant="light"
                            color="red"
                            leftSection={<IconTrash size={16} />}
                            onClick={handleDelete}
                        >
                            Delete
                        </Button>
                    </Group>
                </Group>
            </Paper>

            <Paper withBorder p="md" radius="md" mb="lg">
                <Title order={4} mb="md">
                    Thread Info
                </Title>
                <Stack gap="xs">
                    <Group>
                        <Text fw={500} size="sm" w={100}>
                            URI:
                        </Text>
                        <Code>{comment.thread.uri}</Code>
                    </Group>
                    <Group>
                        <Text fw={500} size="sm" w={100}>
                            Title:
                        </Text>
                        <Text size="sm">
                            {comment.thread.title || 'No title'}
                        </Text>
                    </Group>
                    {comment.parent && (
                        <Group>
                            <Text fw={500} size="sm" w={100}>
                                Reply to:
                            </Text>
                            <Anchor
                                component={Link}
                                href={`/admin/comments/${comment.parent.id}`}
                                size="sm"
                            >
                                {comment.parent.author || 'Anonymous'} (#
                                {comment.parent.id})
                            </Anchor>
                        </Group>
                    )}
                    <Group>
                        <Text fw={500} size="sm" w={100}>
                            Replies:
                        </Text>
                        <Text size="sm">{comment.replies_count}</Text>
                    </Group>
                    <Group>
                        <Text fw={500} size="sm" w={100}>
                            Link:
                        </Text>
                        <Anchor
                            href={`${comment.thread.url}#comment-${comment.id}`}
                            target="_blank"
                            size="sm"
                            style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: 4,
                            }}
                        >
                            View on site
                            <IconExternalLink size={14} />
                        </Anchor>
                    </Group>
                </Stack>
            </Paper>

            <Paper withBorder p="md" radius="md">
                <Title order={4} mb="md">
                    Metadata
                </Title>
                <Stack gap="xs">
                    <Group>
                        <Text fw={500} size="sm" w={100}>
                            IP:
                        </Text>
                        <Code>{comment.remote_addr || 'Unknown'}</Code>
                    </Group>
                    <Group>
                        <Text fw={500} size="sm" w={100}>
                            User Agent:
                        </Text>
                        <Text size="sm" style={{ wordBreak: 'break-all' }}>
                            {comment.user_agent || 'Unknown'}
                        </Text>
                    </Group>
                </Stack>
            </Paper>
        </AdminLayout>
    );
}
