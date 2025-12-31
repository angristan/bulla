import { useCallback, useEffect, useState } from 'preact/hooks';
import Api from '../api';
import type { Config, ThreadResponse } from '../api';
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

    if (loading) {
        return (
            <div className={`marge-container marge-theme-${effectiveTheme}`}>
                <div className="marge-loading">Loading comments...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div className={`marge-container marge-theme-${effectiveTheme}`}>
                <div className="marge-error">
                    {error}
                    <button
                        type="button"
                        onClick={loadData}
                        className="marge-btn marge-btn-secondary"
                    >
                        Retry
                    </button>
                </div>
            </div>
        );
    }

    if (!config || !data) return null;

    const sortLabels: Record<SortOrder, string> = {
        oldest: 'Oldest',
        newest: 'Newest',
        popular: 'Popular',
    };

    return (
        <div className={`marge-container marge-theme-${effectiveTheme}`}>
            <div className="marge-header">
                <h3 className="marge-title">
                    {data.total} {data.total === 1 ? 'Comment' : 'Comments'}
                </h3>
                {data.total > 1 && (
                    <div className="marge-sort">
                        <span>Sort by</span>
                        <select
                            className="marge-sort-select"
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
                onSubmit={loadData}
                onConfigRefresh={refreshConfig}
            />

            <div className="marge-comments">
                {data.comments.length === 0 ? (
                    <div className="marge-empty">
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
                            onRefresh={loadData}
                            onConfigRefresh={refreshConfig}
                        />
                    ))
                )}
            </div>

            <div className="marge-footer">
                <a
                    href="https://github.com/angristan/marge"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Powered by Marge
                </a>
            </div>
        </div>
    );
}
