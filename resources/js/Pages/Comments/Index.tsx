import AdminLayout from '@/Layouts/AdminLayout';
import { Link, router } from '@inertiajs/react';
import {
    ActionIcon,
    Avatar,
    Badge,
    Group,
    Menu,
    Pagination,
    Paper,
    SegmentedControl,
    Table,
    Text,
    TextInput,
    Title,
} from '@mantine/core';
import { useDebouncedValue } from '@mantine/hooks';
import {
    IconCheck,
    IconDotsVertical,
    IconEye,
    IconSearch,
    IconTrash,
    IconX,
} from '@tabler/icons-react';
import { useEffect, useState } from 'react';

interface Comment {
    id: number;
    author: string | null;
    email: string | null;
    avatar: string;
    body_excerpt: string;
    status: string;
    email_verified: boolean;
    is_admin: boolean;
    upvotes: number;
    thread_id: number;
    thread_uri: string;
    thread_title: string | null;
    created_at: string;
}

interface CommentsIndexProps {
    comments: {
        data: Comment[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
        };
    };
    filters: {
        status: string;
        search: string | null;
    };
}

const statusColors: Record<string, string> = {
    pending: 'yellow',
    approved: 'green',
    spam: 'red',
    deleted: 'gray',
};

export default function CommentsIndex({
    comments,
    filters,
}: CommentsIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [debouncedSearch] = useDebouncedValue(search, 300);

    // biome-ignore lint/correctness/useExhaustiveDependencies: Only trigger on debounced search change
    useEffect(() => {
        if (debouncedSearch !== (filters.search || '')) {
            router.get(
                '/admin/comments',
                {
                    status: filters.status,
                    search: debouncedSearch || undefined,
                },
                { preserveState: true },
            );
        }
    }, [debouncedSearch]);

    const handleStatusChange = (status: string) => {
        router.get(
            '/admin/comments',
            {
                status,
                search: search || undefined,
            },
            { preserveState: true },
        );
    };

    const handleAction = (commentId: number, action: string) => {
        router.post(
            `/admin/comments/${commentId}/${action}`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleDelete = (commentId: number) => {
        if (confirm('Are you sure you want to delete this comment?')) {
            router.delete(`/admin/comments/${commentId}`, {
                preserveState: true,
            });
        }
    };

    return (
        <AdminLayout>
            <Group justify="space-between" mb="lg">
                <Title order={2}>Comments</Title>
                <Text c="dimmed">{comments.meta.total} total</Text>
            </Group>

            <Paper withBorder p="md" radius="md" mb="lg">
                <Group>
                    <SegmentedControl
                        value={filters.status}
                        onChange={handleStatusChange}
                        data={[
                            { label: 'All', value: 'all' },
                            { label: 'Pending', value: 'pending' },
                            { label: 'Approved', value: 'approved' },
                            { label: 'Spam', value: 'spam' },
                        ]}
                    />
                    <TextInput
                        placeholder="Search comments..."
                        leftSection={<IconSearch size={16} />}
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        style={{ flex: 1 }}
                    />
                </Group>
            </Paper>

            <Paper withBorder radius="md">
                <Table>
                    <Table.Thead>
                        <Table.Tr>
                            <Table.Th>Author</Table.Th>
                            <Table.Th>Comment</Table.Th>
                            <Table.Th>Thread</Table.Th>
                            <Table.Th>Status</Table.Th>
                            <Table.Th>Actions</Table.Th>
                        </Table.Tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {comments.data.map((comment) => (
                            <Table.Tr key={comment.id}>
                                <Table.Td>
                                    <Group gap="sm">
                                        <Avatar
                                            src={comment.avatar}
                                            size="sm"
                                            radius="xl"
                                        />
                                        <div>
                                            <Text size="sm" fw={500}>
                                                {comment.author || 'Anonymous'}
                                            </Text>
                                            <Text size="xs" c="dimmed">
                                                {comment.email || 'No email'}
                                            </Text>
                                        </div>
                                    </Group>
                                </Table.Td>
                                <Table.Td maw={300}>
                                    <Text size="sm" truncate>
                                        {comment.body_excerpt}
                                    </Text>
                                </Table.Td>
                                <Table.Td>
                                    <Text
                                        size="sm"
                                        c="dimmed"
                                        truncate
                                        maw={150}
                                    >
                                        {comment.thread_title ||
                                            comment.thread_uri}
                                    </Text>
                                </Table.Td>
                                <Table.Td>
                                    <Badge
                                        color={statusColors[comment.status]}
                                        variant="light"
                                    >
                                        {comment.status}
                                    </Badge>
                                </Table.Td>
                                <Table.Td>
                                    <Group gap="xs">
                                        <ActionIcon
                                            component={Link}
                                            href={`/admin/comments/${comment.id}`}
                                            variant="subtle"
                                            size="sm"
                                        >
                                            <IconEye size={16} />
                                        </ActionIcon>
                                        <Menu shadow="md" width={150}>
                                            <Menu.Target>
                                                <ActionIcon
                                                    variant="subtle"
                                                    size="sm"
                                                >
                                                    <IconDotsVertical
                                                        size={16}
                                                    />
                                                </ActionIcon>
                                            </Menu.Target>
                                            <Menu.Dropdown>
                                                {comment.status !==
                                                    'approved' && (
                                                    <Menu.Item
                                                        leftSection={
                                                            <IconCheck
                                                                size={14}
                                                            />
                                                        }
                                                        onClick={() =>
                                                            handleAction(
                                                                comment.id,
                                                                'approve',
                                                            )
                                                        }
                                                    >
                                                        Approve
                                                    </Menu.Item>
                                                )}
                                                {comment.status !== 'spam' && (
                                                    <Menu.Item
                                                        leftSection={
                                                            <IconX size={14} />
                                                        }
                                                        onClick={() =>
                                                            handleAction(
                                                                comment.id,
                                                                'spam',
                                                            )
                                                        }
                                                    >
                                                        Mark Spam
                                                    </Menu.Item>
                                                )}
                                                <Menu.Divider />
                                                <Menu.Item
                                                    color="red"
                                                    leftSection={
                                                        <IconTrash size={14} />
                                                    }
                                                    onClick={() =>
                                                        handleDelete(comment.id)
                                                    }
                                                >
                                                    Delete
                                                </Menu.Item>
                                            </Menu.Dropdown>
                                        </Menu>
                                    </Group>
                                </Table.Td>
                            </Table.Tr>
                        ))}
                    </Table.Tbody>
                </Table>

                {comments.meta.last_page > 1 && (
                    <Group justify="center" p="md">
                        <Pagination
                            total={comments.meta.last_page}
                            value={comments.meta.current_page}
                            onChange={(page) => {
                                router.get(
                                    '/admin/comments',
                                    {
                                        ...filters,
                                        page,
                                    },
                                    { preserveState: true },
                                );
                            }}
                        />
                    </Group>
                )}
            </Paper>
        </AdminLayout>
    );
}
