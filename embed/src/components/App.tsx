import { useCallback, useEffect, useState } from 'preact/hooks';
import type { Config, ThreadResponse } from '../api';
import Api from '../api';
import Comment from './Comment';
import CommentForm from './CommentForm';

type SortOrder = 'oldest' | 'newest' | 'popular';

interface AppProps {
    baseUrl: string;
    uri: string;
    pageTitle?: string;
    pageUrl?: string;
    theme?: 'light' | 'dark' | 'auto';
    guest?: boolean;
    defaultSort?: SortOrder;
}

export default function App({
    baseUrl,
    uri,
    pageTitle,
    pageUrl,
    theme = 'auto',
    guest = false,
    defaultSort = 'oldest',
}: AppProps) {
    const [api] = useState(() => new Api(baseUrl, guest));
    const [config, setConfig] = useState<Config | null>(null);
    const [data, setData] = useState<ThreadResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [sort, setSort] = useState<SortOrder>(defaultSort);

    const loadData = useCallback(async () => {
        try {
            setError(null);
            const [configData, commentsData] = await Promise.all([
                api.getConfig(),
                api.getComments(uri, sort),
            ]);
            setConfig(configData);
            setData(commentsData);
        } catch (err) {
            setError(
                err instanceof Error ? err.message : 'Failed to load comments',
            );
        } finally {
            setLoading(false);
        }
    }, [api, uri, sort]);

    // Scroll to and highlight a comment by ID
    const scrollToComment = useCallback((commentId: number) => {
        // Use requestAnimationFrame to ensure DOM has updated
        requestAnimationFrame(() => {
            const commentEl = document.querySelector(
                `[data-comment-id="${commentId}"]`,
            );
            if (commentEl) {
                commentEl.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });

                // Wait for scroll to finish before highlighting
                const onScrollEnd = () => {
                    commentEl.classList.add('bulla-comment-highlight');
                    setTimeout(() => {
                        commentEl.classList.remove('bulla-comment-highlight');
                    }, 5000);
                };

                // Use scrollend event if supported, otherwise fallback to timeout
                if ('onscrollend' in window) {
                    window.addEventListener('scrollend', onScrollEnd, {
                        once: true,
                    });
                } else {
                    // Fallback: wait for smooth scroll to complete (~500ms)
                    setTimeout(onScrollEnd, 500);
                }
            }
        });
    }, []);

    // Handle new comment submission: reload data and scroll to the new comment
    const handleCommentSubmit = useCallback(
        async (newCommentId: number) => {
            await loadData();
            scrollToComment(newCommentId);
        },
        [loadData, scrollToComment],
    );

    // Refresh config only (for GitHub login/logout)
    const refreshConfig = useCallback(async () => {
        try {
            const configData = await api.getConfig();
            setConfig(configData);
        } catch {
            // Ignore errors - config refresh is not critical
        }
    }, [api]);

    useEffect(() => {
        loadData();
    }, [loadData]);

    // Handle URL hash on initial load to scroll to comment
    useEffect(() => {
        if (!loading && data) {
            const hash = window.location.hash;
            if (hash?.startsWith('#comment-')) {
                const commentId = Number.parseInt(
                    hash.replace('#comment-', ''),
                    10,
                );
                if (!Number.isNaN(commentId)) {
                    scrollToComment(commentId);
                }
            }
        }
    }, [loading, data, scrollToComment]);

    // Determine effective theme
    const getEffectiveTheme = (): 'light' | 'dark' => {
        if (theme === 'auto') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'dark'
                : 'light';
        }
        return theme;
    };

    const [effectiveTheme, setEffectiveTheme] = useState(getEffectiveTheme);

    // Update effective theme when theme prop changes
    useEffect(() => {
        if (theme === 'auto') {
            setEffectiveTheme(
                window.matchMedia('(prefers-color-scheme: dark)').matches
                    ? 'dark'
                    : 'light',
            );
        } else {
            setEffectiveTheme(theme);
        }
    }, [theme]);

    useEffect(() => {
        if (theme !== 'auto') return;

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const handler = () =>
            setEffectiveTheme(mediaQuery.matches ? 'dark' : 'light');
        mediaQuery.addEventListener('change', handler);
        return () => mediaQuery.removeEventListener('change', handler);
    }, [theme]);

    // Generate CSS custom properties for accent color
    const getAccentStyles = (accentColor?: string) => {
        if (!accentColor) return {};
        return {
            '--bulla-primary': accentColor,
            '--bulla-primary-hover': accentColor,
        } as React.CSSProperties;
    };

    if (loading) {
        return (
            <div className={`bulla-container bulla-theme-${effectiveTheme}`}>
                <div className="bulla-loading">Loading comments...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div className={`bulla-container bulla-theme-${effectiveTheme}`}>
                <div className="bulla-error">
                    {error}
                    <button
                        type="button"
                        onClick={loadData}
                        className="bulla-btn bulla-btn-secondary"
                    >
                        Retry
                    </button>
                </div>
            </div>
        );
    }

    if (!config || !data) return null;

    const accentStyles = getAccentStyles(config.accent_color);

    const sortLabels: Record<SortOrder, string> = {
        oldest: 'Oldest',
        newest: 'Newest',
        popular: 'Popular',
    };

    return (
        <div
            className={`bulla-container bulla-theme-${effectiveTheme}`}
            style={accentStyles}
        >
            <div className="bulla-header">
                <h3 className="bulla-title">
                    {data.total} {data.total === 1 ? 'Comment' : 'Comments'}
                </h3>
                {data.total > 1 && (
                    <div className="bulla-sort">
                        <span>Sort by</span>
                        <select
                            className="bulla-sort-select"
                            value={sort}
                            onChange={(e) =>
                                setSort(e.currentTarget.value as SortOrder)
                            }
                        >
                            <option value="oldest">{sortLabels.oldest}</option>
                            <option value="newest">{sortLabels.newest}</option>
                            <option value="popular">
                                {sortLabels.popular}
                            </option>
                        </select>
                    </div>
                )}
            </div>

            <CommentForm
                api={api}
                config={config}
                uri={uri}
                pageTitle={pageTitle}
                pageUrl={pageUrl}
                onSubmit={handleCommentSubmit}
                onConfigRefresh={refreshConfig}
            />

            <div className="bulla-comments">
                {data.comments.length === 0 ? (
                    <div className="bulla-empty">
                        No comments yet. Be the first to comment!
                    </div>
                ) : (
                    data.comments.map((comment) => (
                        <Comment
                            key={comment.id}
                            comment={comment}
                            api={api}
                            config={config}
                            uri={uri}
                            depth={0}
                            onRefresh={handleCommentSubmit}
                            onConfigRefresh={refreshConfig}
                        />
                    ))
                )}
            </div>

            <div className="bulla-footer">
                <a
                    href="https://github.com/angristan/bulla"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Powered by Bulla
                </a>
            </div>
        </div>
    );
}
