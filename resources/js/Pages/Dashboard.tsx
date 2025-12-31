import AdminLayout from '@/Layouts/AdminLayout';
import { Link } from '@inertiajs/react';
import { BarChart } from '@mantine/charts';
import {
    ActionIcon,
    Badge,
    Button,
    Code,
    CopyButton,
    Grid,
    Group,
    Paper,
    Table,
    Text,
    Title,
} from '@mantine/core';
import { IconCheck, IconCopy, IconEye } from '@tabler/icons-react';

interface Comment {
    id: number;
    author: string | null;
    body_excerpt: string;
    status: string;
    thread_uri: string;
    thread_title: string | null;
    created_at: string;
}

interface DashboardProps {
    stats: {
        total_comments: number;
        pending_comments: number;
        approved_comments: number;
        spam_comments: number;
        total_threads: number;
        recent_comments: Comment[];
        comments_per_month: Array<{ date: string; count: number }>;
    };
    siteName: string;
    siteUrl: string | null;
}

function formatChartMonth(monthStr: string): string {
    const [year, month] = monthStr.split('-');
    const date = new Date(Number.parseInt(year), Number.parseInt(month) - 1);
    return date.toLocaleDateString('en-US', { month: 'short' });
}

const statusColors: Record<string, string> = {
    pending: 'yellow',
    approved: 'green',
    spam: 'red',
    deleted: 'gray',
};

export default function Dashboard({
    stats,
    siteName,
    siteUrl,
}: DashboardProps) {
    const bullaUrl = window.location.origin;
    const embedCode = `<script src="${bullaUrl}/embed/embed.js" data-bulla="${bullaUrl}" async></script>
<div id="bulla-thread"></div>`;

    return (
        <AdminLayout>
            <Title order={2} mb="lg">
                Dashboard
            </Title>

            <Grid mb="lg">
                <Grid.Col span={{ base: 6, md: 3 }}>
                    <Paper withBorder p="md" radius="md">
                        <Text size="xs" c="dimmed" tt="uppercase" fw={700}>
                            Total Comments
                        </Text>
                        <Text size="xl" fw={700}>
                            {stats.total_comments}
                        </Text>
                    </Paper>
                </Grid.Col>
                <Grid.Col span={{ base: 6, md: 3 }}>
                    <Paper withBorder p="md" radius="md">
                        <Text size="xs" c="dimmed" tt="uppercase" fw={700}>
                            Pending
                        </Text>
                        <Text size="xl" fw={700} c="yellow">
                            {stats.pending_comments}
                        </Text>
                    </Paper>
                </Grid.Col>
                <Grid.Col span={{ base: 6, md: 3 }}>
                    <Paper withBorder p="md" radius="md">
                        <Text size="xs" c="dimmed" tt="uppercase" fw={700}>
                            Approved
                        </Text>
                        <Text size="xl" fw={700} c="green">
                            {stats.approved_comments}
                        </Text>
                    </Paper>
                </Grid.Col>
                <Grid.Col span={{ base: 6, md: 3 }}>
                    <Paper withBorder p="md" radius="md">
                        <Text size="xs" c="dimmed" tt="uppercase" fw={700}>
                            Spam
                        </Text>
                        <Text size="xl" fw={700} c="red">
                            {stats.spam_comments}
                        </Text>
                    </Paper>
                </Grid.Col>
            </Grid>

            <Grid>
                <Grid.Col span={{ base: 12, md: 8 }}>
                    <Paper withBorder p="md" radius="md" mb="lg">
                        <Text size="sm" fw={500} mb="md">
                            Comments (Last 12 Months)
                        </Text>
                        <BarChart
                            h={250}
                            data={stats.comments_per_month}
                            dataKey="date"
                            gridProps={{ yAxisId: 'left' }}
                            series={[
                                {
                                    name: 'count',
                                    label: 'Comments',
                                    color: 'indigo',
                                },
                            ]}
                            tickLine="x"
                            xAxisProps={{
                                tickFormatter: formatChartMonth,
                            }}
                        />
                    </Paper>

                    <Paper withBorder p="md" radius="md">
                        <Group justify="space-between" mb="md">
                            <Text size="sm" fw={500}>
                                Recent Comments
                            </Text>
                            <Button
                                component={Link}
                                href="/admin/comments"
                                variant="subtle"
                                size="xs"
                            >
                                View All
                            </Button>
                        </Group>
                        <Table>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Author</Table.Th>
                                    <Table.Th>Comment</Table.Th>
                                    <Table.Th>Status</Table.Th>
                                    <Table.Th>Actions</Table.Th>
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {stats.recent_comments.map((comment) => (
                                    <Table.Tr key={comment.id}>
                                        <Table.Td>
                                            {comment.author || 'Anonymous'}
                                        </Table.Td>
                                        <Table.Td maw={300}>
                                            <Text size="sm" truncate>
                                                {comment.body_excerpt}
                                            </Text>
                                        </Table.Td>
                                        <Table.Td>
                                            <Badge
                                                color={
                                                    statusColors[comment.status]
                                                }
                                                variant="light"
                                            >
                                                {comment.status}
                                            </Badge>
                                        </Table.Td>
                                        <Table.Td>
                                            <ActionIcon
                                                component={Link}
                                                href={`/admin/comments/${comment.id}`}
                                                variant="subtle"
                                                size="sm"
                                            >
                                                <IconEye size={16} />
                                            </ActionIcon>
                                        </Table.Td>
                                    </Table.Tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    </Paper>
                </Grid.Col>

                <Grid.Col span={{ base: 12, md: 4 }}>
                    <Paper withBorder p="md" radius="md">
                        <Text size="sm" fw={500} mb="md">
                            Embed Code
                        </Text>
                        <Text size="xs" c="dimmed" mb="sm">
                            Add this code to your website where you want
                            comments to appear:
                        </Text>
                        <Code
                            block
                            style={{ fontSize: '11px', whiteSpace: 'pre-wrap' }}
                        >
                            {embedCode}
                        </Code>
                        <CopyButton value={embedCode}>
                            {({ copied, copy }) => (
                                <Button
                                    fullWidth
                                    mt="sm"
                                    variant="light"
                                    onClick={copy}
                                    leftSection={
                                        copied ? (
                                            <IconCheck size={16} />
                                        ) : (
                                            <IconCopy size={16} />
                                        )
                                    }
                                >
                                    {copied ? 'Copied!' : 'Copy Code'}
                                </Button>
                            )}
                        </CopyButton>
                    </Paper>
                </Grid.Col>
            </Grid>
        </AdminLayout>
    );
}
