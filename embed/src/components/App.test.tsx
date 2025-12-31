import { cleanup, render, screen, waitFor } from '@testing-library/preact';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { Config, ThreadResponse } from '../api';
import App from './App';

vi.mock('../api', () => {
    return {
        default: vi.fn().mockImplementation(function () {
            return {
                getConfig: vi.fn(),
                getComments: vi.fn(),
            };
        }),
    };
});

const mockConfig: Config = {
    site_name: 'Test Site',
    require_author: false,
    require_email: false,
    moderation_mode: 'none',
    max_depth: 3,
    edit_window_minutes: 5,
    timestamp: '2024-01-01T00:00:00Z',
    is_admin: false,
    enable_upvotes: true,
    enable_downvotes: false,
    admin_badge_label: 'Admin',
    github_auth_enabled: false,
    commenter: null,
};

const mockThread: ThreadResponse = {
    thread: { id: 1, uri: '/test', title: 'Test' },
    comments: [],
    total: 0,
};

describe('App', () => {
    let mockApi: {
        getConfig: ReturnType<typeof vi.fn>;
        getComments: ReturnType<typeof vi.fn>;
    };

    beforeEach(async () => {
        const ApiModule = await import('../api');
        mockApi = {
            getConfig: vi.fn().mockResolvedValue(mockConfig),
            getComments: vi.fn().mockResolvedValue(mockThread),
        };
        vi.mocked(ApiModule.default).mockImplementation(function () {
            return mockApi as any;
        });
    });

    afterEach(() => {
        cleanup();
        vi.clearAllMocks();
    });

    it('renders loading state initially', () => {
        render(<App baseUrl="https://example.com" uri="/test" />);

        expect(screen.getByText('Loading comments...')).toBeInTheDocument();
    });

    it('renders comments after loading', async () => {
        render(<App baseUrl="https://example.com" uri="/test" />);

        await waitFor(() => {
            expect(screen.getByText('0 Comments')).toBeInTheDocument();
        });
    });

    it('renders error state with retry button', async () => {
        mockApi.getConfig.mockRejectedValueOnce(new Error('Network error'));

        render(<App baseUrl="https://example.com" uri="/test" />);

        await waitFor(() => {
            expect(screen.getByText('Network error')).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Retry' }),
            ).toBeInTheDocument();
        });
    });

    it("shows 'No comments yet' when there are no comments", async () => {
        render(<App baseUrl="https://example.com" uri="/test" />);

        await waitFor(() => {
            expect(
                screen.getByText('No comments yet. Be the first to comment!'),
            ).toBeInTheDocument();
        });
    });

    it('renders comments when they exist', async () => {
        mockApi.getComments.mockResolvedValueOnce({
            thread: { id: 1, uri: '/test', title: 'Test' },
            comments: [
                {
                    id: 1,
                    parent_id: null,
                    parent_author: null,
                    depth: 0,
                    author: 'Test User',
                    is_admin: false,
                    is_github_user: false,
                    github_username: null,
                    avatar: 'https://example.com/avatar.png',
                    website: null,
                    body_html: '<p>Test comment</p>',
                    upvotes: 0,
                    downvotes: 0,
                    created_at: '2024-01-01T00:00:00Z',
                    replies: [],
                },
            ],
            total: 1,
        });

        render(<App baseUrl="https://example.com" uri="/test" />);

        await waitFor(() => {
            expect(screen.getByText('1 Comment')).toBeInTheDocument();
            expect(screen.getByText('Test User')).toBeInTheDocument();
        });
    });

    it('shows sort dropdown when there are multiple comments', async () => {
        mockApi.getComments.mockResolvedValueOnce({
            thread: { id: 1, uri: '/test', title: 'Test' },
            comments: [
                {
                    id: 1,
                    parent_id: null,
                    parent_author: null,
                    depth: 0,
                    author: 'User 1',
                    is_admin: false,
                    is_github_user: false,
                    github_username: null,
                    avatar: 'https://example.com/avatar.png',
                    website: null,
                    body_html: '<p>Comment 1</p>',
                    upvotes: 0,
                    downvotes: 0,
                    created_at: '2024-01-01T00:00:00Z',
                    replies: [],
                },
                {
                    id: 2,
                    parent_id: null,
                    parent_author: null,
                    depth: 0,
                    author: 'User 2',
                    is_admin: false,
                    is_github_user: false,
                    github_username: null,
                    avatar: 'https://example.com/avatar.png',
                    website: null,
                    body_html: '<p>Comment 2</p>',
                    upvotes: 0,
                    downvotes: 0,
                    created_at: '2024-01-01T00:00:00Z',
                    replies: [],
                },
            ],
            total: 2,
        });

        render(<App baseUrl="https://example.com" uri="/test" />);

        await waitFor(() => {
            expect(screen.getByText('Sort by')).toBeInTheDocument();
            expect(screen.getByRole('combobox')).toBeInTheDocument();
        });
    });

    it('applies light theme class', async () => {
        render(<App baseUrl="https://example.com" uri="/test" theme="light" />);

        await waitFor(() => {
            const container = document.querySelector('.bulla-container');
            expect(container).toHaveClass('bulla-theme-light');
        });
    });

    it('applies dark theme class', async () => {
        render(<App baseUrl="https://example.com" uri="/test" theme="dark" />);

        await waitFor(() => {
            const container = document.querySelector('.bulla-container');
            expect(container).toHaveClass('bulla-theme-dark');
        });
    });

    it('updates theme reactively when prop changes', async () => {
        const { rerender } = render(
            <App baseUrl="https://example.com" uri="/test" theme="dark" />,
        );

        await waitFor(() => {
            const container = document.querySelector('.bulla-container');
            expect(container).toHaveClass('bulla-theme-dark');
        });

        rerender(
            <App baseUrl="https://example.com" uri="/test" theme="light" />,
        );

        await waitFor(() => {
            const container = document.querySelector('.bulla-container');
            expect(container).toHaveClass('bulla-theme-light');
        });
    });

    it('renders footer with link to Bulla', async () => {
        render(<App baseUrl="https://example.com" uri="/test" />);

        await waitFor(() => {
            const link = screen.getByRole('link', { name: 'Powered by Bulla' });
            expect(link).toHaveAttribute(
                'href',
                'https://github.com/angristan/bulla',
            );
        });
    });
});
