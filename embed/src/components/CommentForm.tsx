import { useEffect, useState } from 'preact/hooks';
import type Api from '../api';
import type { Config } from '../api';
import GitHubLoginButton from './GitHubLoginButton';

interface CommentFormProps {
    api: Api;
    config: Config;
    uri: string;
    pageTitle?: string;
    pageUrl?: string;
    parentId?: number | null;
    onSubmit: (newCommentId: number) => void;
    onConfigRefresh: () => void;
}

export default function CommentForm({
    api,
    config,
    uri,
    pageTitle,
    pageUrl,
    parentId,
    onSubmit,
    onConfigRefresh,
}: CommentFormProps) {
    const [author, setAuthor] = useState('');
    const [email, setEmail] = useState('');
    const [website, setWebsite] = useState('');
    const [body, setBody] = useState('');
    const [notifyReplies, setNotifyReplies] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [previewMode, setPreviewMode] = useState(false);
    const [previewHtml, setPreviewHtml] = useState('');
    const [previewLoading, setPreviewLoading] = useState(false);

    const handlePreviewToggle = async (showPreview: boolean) => {
        if (showPreview && body.trim()) {
            setPreviewLoading(true);
            try {
                const { html } = await api.previewMarkdown(body);
                setPreviewHtml(html);
            } catch {
                setPreviewHtml('<p>Failed to load preview</p>');
            } finally {
                setPreviewLoading(false);
            }
        }
        setPreviewMode(showPreview);
    };

    const handleSubmit = async (e: Event) => {
        e.preventDefault();
        setError(null);
        setSubmitting(true);

        try {
            const result = await api.createComment(uri, {
                parent_id: parentId,
                author: config.is_admin ? undefined : author || undefined,
                email: config.is_admin ? undefined : email || undefined,
                website: website || undefined,
                body,
                notify_replies: notifyReplies,
                title: pageTitle,
                url: pageUrl,
                honeypot: '', // Empty honeypot
                timestamp: config.timestamp,
            });

            setBody('');
            setAuthor('');
            setEmail('');
            setWebsite('');
            setNotifyReplies(false);
            onSubmit(result.id);
        } catch (err) {
            setError(
                err instanceof Error ? err.message : 'Failed to post comment',
            );
        } finally {
            setSubmitting(false);
        }
    };

    // Whether the user is authenticated via GitHub
    const isGitHubAuthenticated = config.commenter !== null;

    // Show auth section for non-admin users when GitHub auth is enabled
    const showAuthSection = !config.is_admin && config.github_auth_enabled;

    // Hide name/email fields if logged in via GitHub or as admin
    const hideIdentityFields = config.is_admin || isGitHubAuthenticated;

    // For notify replies, use commenter email if available
    const effectiveEmail = config.commenter?.email || email;

    // Auto-enable notify toggle when user logs in with GitHub
    useEffect(() => {
        if (isGitHubAuthenticated && config.commenter?.email) {
            setNotifyReplies(true);
        }
    }, [isGitHubAuthenticated, config.commenter?.email]);

    const handleLogout = async () => {
        await api.logout();
        onConfigRefresh();
    };

    return (
        <form className="bulla-form" onSubmit={handleSubmit}>
            {error && <div className="bulla-error">{error}</div>}

            {showAuthSection && (
                <div className="bulla-auth-section">
                    {isGitHubAuthenticated ? (
                        <div className="bulla-commenter-info">
                            <a
                                href={
                                    config.commenter?.github_username
                                        ? `https://github.com/${config.commenter?.github_username}`
                                        : undefined
                                }
                                target="_blank"
                                rel="noopener noreferrer"
                                className="bulla-commenter-profile"
                            >
                                {config.commenter?.github_username && (
                                    <img
                                        src={`https://github.com/${config.commenter?.github_username}.png`}
                                        alt=""
                                        className="bulla-commenter-avatar"
                                    />
                                )}
                                <span className="bulla-commenter-name">
                                    {config.commenter?.name}
                                    {config.commenter?.github_username && (
                                        <span className="bulla-commenter-username">
                                            {' '}
                                            (@
                                            {config.commenter?.github_username})
                                        </span>
                                    )}
                                </span>
                            </a>
                            <button
                                type="button"
                                className="bulla-btn-logout"
                                onClick={handleLogout}
                            >
                                <svg
                                    viewBox="0 0 16 16"
                                    width="14"
                                    height="14"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fillRule="evenodd"
                                        d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"
                                    />
                                </svg>
                                Logout
                            </button>
                        </div>
                    ) : (
                        <>
                            <GitHubLoginButton
                                authUrl={api.getGitHubAuthUrl()}
                                onSuccess={onConfigRefresh}
                            />
                            <span className="bulla-auth-divider">
                                or comment as guest
                            </span>
                        </>
                    )}
                </div>
            )}

            {!hideIdentityFields && (
                <>
                    <div className="bulla-form-row">
                        <input
                            type="text"
                            className="bulla-input"
                            placeholder={
                                config.require_author
                                    ? 'Name *'
                                    : 'Name (optional)'
                            }
                            value={author}
                            onInput={(e) =>
                                setAuthor((e.target as HTMLInputElement).value)
                            }
                            required={config.require_author}
                        />
                        <input
                            type="email"
                            className="bulla-input"
                            placeholder={
                                config.require_email
                                    ? 'Email *'
                                    : 'Email (optional)'
                            }
                            value={email}
                            onInput={(e) => {
                                const newEmail = (e.target as HTMLInputElement)
                                    .value;
                                setEmail(newEmail);
                                if (newEmail.trim()) {
                                    setNotifyReplies(true);
                                } else {
                                    setNotifyReplies(false);
                                }
                            }}
                            required={config.require_email}
                        />
                    </div>

                    <input
                        type="text"
                        className="bulla-input"
                        placeholder="Website (optional)"
                        value={website}
                        onInput={(e) =>
                            setWebsite((e.target as HTMLInputElement).value)
                        }
                    />
                </>
            )}

            <textarea
                className="bulla-textarea"
                placeholder="Write your comment... (Markdown supported)"
                value={body}
                onInput={(e) =>
                    setBody((e.target as HTMLTextAreaElement).value)
                }
                required
                rows={4}
                style={{ display: previewMode ? 'none' : undefined }}
            />
            {previewMode && (
                <div className="bulla-preview">
                    {previewLoading ? (
                        <div className="bulla-preview-loading">Loading...</div>
                    ) : (
                        <div
                            className="bulla-comment-body"
                            dangerouslySetInnerHTML={{ __html: previewHtml }}
                        />
                    )}
                </div>
            )}

            {/* Honeypot field - hidden from users */}
            <input
                type="text"
                name="website_url"
                style={{ display: 'none' }}
                tabIndex={-1}
                autoComplete="off"
            />

            <div className="bulla-form-footer">
                <label
                    className="bulla-checkbox"
                    title={
                        !effectiveEmail?.trim()
                            ? 'Enter an email to enable notifications'
                            : undefined
                    }
                >
                    <input
                        type="checkbox"
                        checked={notifyReplies}
                        onChange={(e) =>
                            setNotifyReplies(
                                (e.target as HTMLInputElement).checked,
                            )
                        }
                        disabled={!effectiveEmail?.trim()}
                    />
                    <span>Notify me of replies</span>
                </label>

                <div className="bulla-form-actions">
                    <button
                        type="button"
                        className="bulla-btn bulla-btn-secondary"
                        onClick={() => handlePreviewToggle(!previewMode)}
                        disabled={!body.trim() && !previewMode}
                    >
                        {previewMode ? 'Edit' : 'Preview'}
                    </button>
                    <button
                        type="submit"
                        className="bulla-btn bulla-btn-primary"
                        disabled={submitting || !body.trim()}
                    >
                        {submitting
                            ? 'Posting...'
                            : config.is_admin
                              ? 'Post as Admin'
                              : parentId
                                ? 'Reply'
                                : 'Post Comment'}
                    </button>
                </div>
            </div>
        </form>
    );
}
