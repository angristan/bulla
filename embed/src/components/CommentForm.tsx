import { useState } from 'preact/hooks';
import type Api from '../api';
import type { Config } from '../api';

interface CommentFormProps {
    api: Api;
    config: Config;
    uri: string;
    pageTitle?: string;
    pageUrl?: string;
    parentId?: number | null;
    onSubmit: () => void;
    onCancel?: () => void;
}

export default function CommentForm({
    api,
    config,
    uri,
    pageTitle,
    pageUrl,
    parentId,
    onSubmit,
    onCancel,
}: CommentFormProps) {
    const [author, setAuthor] = useState('');
    const [email, setEmail] = useState('');
    const [website, setWebsite] = useState('');
    const [body, setBody] = useState('');
    const [notifyReplies, setNotifyReplies] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: Event) => {
        e.preventDefault();
        setError(null);
        setSubmitting(true);

        try {
            await api.createComment(uri, {
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
            onSubmit();
        } catch (err) {
            setError(
                err instanceof Error ? err.message : 'Failed to post comment',
            );
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <form className="marge-form" onSubmit={handleSubmit}>
            {error && <div className="marge-error">{error}</div>}

            {!config.is_admin && (
                <>
                    <div className="marge-form-row">
                        <input
                            type="text"
                            className="marge-input"
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
                            className="marge-input"
                            placeholder={
                                config.require_email
                                    ? 'Email *'
                                    : 'Email (optional)'
                            }
                            value={email}
                            onInput={(e) =>
                                setEmail((e.target as HTMLInputElement).value)
                            }
                            required={config.require_email}
                        />
                    </div>

                    <input
                        type="text"
                        className="marge-input"
                        placeholder="Website (optional)"
                        value={website}
                        onInput={(e) =>
                            setWebsite((e.target as HTMLInputElement).value)
                        }
                    />
                </>
            )}

            <textarea
                className="marge-textarea"
                placeholder="Write your comment... (Markdown supported)"
                value={body}
                onInput={(e) =>
                    setBody((e.target as HTMLTextAreaElement).value)
                }
                required
                rows={4}
            />

            {/* Honeypot field - hidden from users */}
            <input
                type="text"
                name="website_url"
                style={{ display: 'none' }}
                tabIndex={-1}
                autoComplete="off"
            />

            <div className="marge-form-footer">
                <label className="marge-checkbox">
                    <input
                        type="checkbox"
                        checked={notifyReplies}
                        onChange={(e) =>
                            setNotifyReplies(
                                (e.target as HTMLInputElement).checked,
                            )
                        }
                    />
                    <span>Notify me of replies</span>
                </label>

                <div className="marge-form-actions">
                    {onCancel && (
                        <button
                            type="button"
                            className="marge-btn marge-btn-secondary"
                            onClick={onCancel}
                        >
                            Cancel
                        </button>
                    )}
                    <button
                        type="submit"
                        className="marge-btn marge-btn-primary"
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
