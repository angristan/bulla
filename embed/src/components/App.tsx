import { useCallback, useEffect, useState } from 'preact/hooks';
import Api from '../api';
import type { Config, ThreadResponse } from '../api';
import Comment from './Comment';
import CommentForm from './CommentForm';

interface AppProps {
    baseUrl: string;
    uri: string;
    pageTitle?: string;
    pageUrl?: string;
    theme?: 'light' | 'dark' | 'auto';
}

export default function App({
    baseUrl,
    uri,
    pageTitle,
    pageUrl,
    theme = 'auto',
}: AppProps) {
    const [api] = useState(() => new Api(baseUrl));
    const [config, setConfig] = useState<Config | null>(null);
    const [data, setData] = useState<ThreadResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const loadData = useCallback(async () => {
        try {
            setError(null);
            const [configData, commentsData] = await Promise.all([
                api.getConfig(),
                api.getComments(uri),
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
    }, [api, uri]);

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
            <div className={`opaska-container opaska-theme-${effectiveTheme}`}>
                <div className="opaska-loading">Loading comments...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div className={`opaska-container opaska-theme-${effectiveTheme}`}>
                <div className="opaska-error">
                    {error}
                    <button
                        type="button"
                        onClick={loadData}
                        className="opaska-btn opaska-btn-secondary"
                    >
                        Retry
                    </button>
                </div>
            </div>
        );
    }

    if (!config || !data) return null;

    return (
        <div className={`opaska-container opaska-theme-${effectiveTheme}`}>
            <div className="opaska-header">
                <h3 className="opaska-title">
                    {data.total} {data.total === 1 ? 'Comment' : 'Comments'}
                </h3>
            </div>

            <CommentForm
                api={api}
                config={config}
                uri={uri}
                pageTitle={pageTitle}
                pageUrl={pageUrl}
                onSubmit={loadData}
            />

            <div className="opaska-comments">
                {data.comments.length === 0 ? (
                    <div className="opaska-empty">
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
                        />
                    ))
                )}
            </div>

            <div className="opaska-footer">
                <a
                    href="https://github.com/your-repo/opaska"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Powered by Opaska
                </a>
            </div>
        </div>
    );
}
