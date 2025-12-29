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
                author: author || undefined,
                email: email || undefined,
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
        <form className="opaska-form" onSubmit={handleSubmit}>
            {error && <div className="opaska-error">{error}</div>}

            <div className="opaska-form-row">
                <input
                    type="text"
                    className="opaska-input"
                    placeholder={
                        config.require_author ? 'Name *' : 'Name (optional)'
                    }
                    value={author}
                    onInput={(e) =>
                        setAuthor((e.target as HTMLInputElement).value)
                    }
                    required={config.require_author}
                />
                <input
                    type="email"
                    className="opaska-input"
                    placeholder={
                        config.require_email ? 'Email *' : 'Email (optional)'
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
                className="opaska-input"
                placeholder="Website (optional)"
                value={website}
                onInput={(e) =>
                    setWebsite((e.target as HTMLInputElement).value)
                }
            />

            <textarea
                className="opaska-textarea"
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

            <div className="opaska-form-footer">
                <label className="opaska-checkbox">
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

                <div className="opaska-form-actions">
                    {onCancel && (
                        <button
                            type="button"
                            className="opaska-btn opaska-btn-secondary"
                            onClick={onCancel}
                        >
                            Cancel
                        </button>
                    )}
                    <button
                        type="submit"
                        className="opaska-btn opaska-btn-primary"
                        disabled={submitting || !body.trim()}
                    >
                        {submitting
                            ? 'Posting...'
                            : parentId
                              ? 'Reply'
                              : 'Post Comment'}
                    </button>
                </div>
            </div>
        </form>
    );
}
