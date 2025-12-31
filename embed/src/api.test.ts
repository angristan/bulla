import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { Config, CreateCommentResponse, ThreadResponse } from './api';
import Api from './api';

describe('Api', () => {
    let api: Api;

    beforeEach(() => {
        api = new Api('https://example.com');
        vi.stubGlobal('fetch', vi.fn());
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    describe('constructor', () => {
        it('removes trailing slash from baseUrl', () => {
            const apiWithSlash = new Api('https://example.com/');
            expect(apiWithSlash.getGitHubAuthUrl()).toBe(
                'https://example.com/auth/github/redirect',
            );
        });
    });

    describe('getConfig', () => {
        it('fetches config from api', async () => {
            const mockConfig: Config = {
                site_name: 'Test Site',
                require_author: true,
                require_email: false,
                moderation_mode: 'none',
                max_depth: 3,
                edit_window_minutes: 5,
                timestamp: '2024-01-01T00:00:00Z',
                is_admin: false,
                enable_upvotes: true,
                enable_downvotes: false,
                admin_badge_label: 'Admin',
                github_auth_enabled: true,
                commenter: null,
            };

            vi.mocked(fetch).mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockConfig),
            } as Response);

            const result = await api.getConfig();

            expect(fetch).toHaveBeenCalledWith(
                'https://example.com/api/config',
                {
                    credentials: 'include',
                },
            );
            expect(result).toEqual(mockConfig);
        });

        it('adds guest param when guest mode enabled', async () => {
            const guestApi = new Api('https://example.com', true);

            vi.mocked(fetch).mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({}),
            } as Response);

            await guestApi.getConfig();

            expect(fetch).toHaveBeenCalledWith(
                'https://example.com/api/config?guest=1',
                { credentials: 'include' },
            );
        });

        it('throws error on failed response', async () => {
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
            } as Response);

            await expect(api.getConfig()).rejects.toThrow(
                'Failed to load config',
            );
        });
    });

    describe('getComments', () => {
        it('fetches comments for a thread', async () => {
            const mockResponse: ThreadResponse = {
                thread: { id: 1, uri: '/test', title: 'Test' },
                comments: [],
                total: 0,
            };

            vi.mocked(fetch).mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockResponse),
            } as Response);

            const result = await api.getComments('/test');

            expect(fetch).toHaveBeenCalledWith(
                'https://example.com/api/threads/%2Ftest/comments',
            );
            expect(result).toEqual(mockResponse);
        });

        it('adds sort param when specified', async () => {
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: true,
                json: () =>
                    Promise.resolve({ thread: {}, comments: [], total: 0 }),
            } as Response);

            await api.getComments('/test', 'newest');

            expect(fetch).toHaveBeenCalledWith(
                'https://example.com/api/threads/%2Ftest/comments?sort=newest',
            );
        });

        it('throws error on failed response', async () => {
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
            } as Response);

            await expect(api.getComments('/test')).rejects.toThrow(
                'Failed to load comments',
            );
        });
    });

    describe('createComment', () => {
        it('posts a new comment', async () => {
            const mockResponse: CreateCommentResponse = {
                id: 1,
                author: 'Test',
                is_admin: false,
                is_github_user: false,
                avatar: 'https://example.com/avatar.png',
                website: null,
                body_html: '<p>Test</p>',
                status: 'approved',
                upvotes: 0,
                downvotes: 0,
                created_at: '2024-01-01T00:00:00Z',
                edit_token: 'token',
                edit_token_expires_at: '2024-01-01T00:05:00Z',
            };

            vi.mocked(fetch).mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockResponse),
            } as Response);

            const result = await api.createComment('/test', {
                body: 'Test comment',
                author: 'Test',
            });

            expect(fetch).toHaveBeenCalledWith(
                'https://example.com/api/threads/%2Ftest/comments',
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        body: 'Test comment',
                        author: 'Test',
                    }),
                    credentials: 'include',
                },
            );
            expect(result).toEqual(mockResponse);
        });

        it('throws error with message from response', async () => {
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
                json: () => Promise.resolve({ error: 'Validation failed' }),
            } as Response);

            await expect(
                api.createComment('/test', { body: 'Test' }),
            ).rejects.toThrow('Validation failed');
        });
    });

    describe('upvoteComment', () => {
        it('upvotes a comment', async () => {
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({ upvotes: 5 }),
            } as Response);

            const result = await api.upvoteComment(123);

            expect(fetch).toHaveBeenCalledWith(
                'https://example.com/api/comments/123/upvote',
                { method: 'POST' },
            );
            expect(result).toEqual({ upvotes: 5 });
        });

        it('throws error on failure', async () => {
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
                json: () => Promise.resolve({ error: 'Already voted' }),
            } as Response);

            await expect(api.upvoteComment(123)).rejects.toThrow(
                'Already voted',
            );
        });
    });

    describe('downvoteComment', () => {
        it('downvotes a comment', async () => {
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({ downvotes: 2 }),
            } as Response);

            const result = await api.downvoteComment(123);

            expect(fetch).toHaveBeenCalledWith(
                'https://example.com/api/comments/123/downvote',
                { method: 'POST' },
            );
            expect(result).toEqual({ downvotes: 2 });
        });
    });

    describe('previewMarkdown', () => {
        it('previews markdown content', async () => {
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: true,
                json: () =>
                    Promise.resolve({ html: '<p><strong>bold</strong></p>' }),
            } as Response);

            const result = await api.previewMarkdown('**bold**');

            expect(fetch).toHaveBeenCalledWith(
                'https://example.com/api/comments/preview',
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ body: '**bold**' }),
                },
            );
            expect(result).toEqual({ html: '<p><strong>bold</strong></p>' });
        });
    });

    describe('logout', () => {
        it('posts to logout endpoint', async () => {
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: true,
            } as Response);

            await api.logout();

            expect(fetch).toHaveBeenCalledWith(
                'https://example.com/auth/logout',
                { method: 'POST', credentials: 'include' },
            );
        });
    });

    describe('getGitHubAuthUrl', () => {
        it('returns the github auth redirect url', () => {
            expect(api.getGitHubAuthUrl()).toBe(
                'https://example.com/auth/github/redirect',
            );
        });
    });
});
