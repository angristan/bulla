import { useState } from 'preact/hooks';
import type Api from '../api';
import type { Comment as CommentType, Config } from '../api';
import CommentForm from './CommentForm';

interface CommentProps {
    comment: CommentType;
    api: Api;
    config: Config;
    uri: string;
    depth: number;
    onRefresh: () => void;
}

export default function Comment({
    comment,
    api,
    config,
    uri,
    depth,
    onRefresh,
}: CommentProps) {
    const [showReplyForm, setShowReplyForm] = useState(false);
    const [upvotes, setUpvotes] = useState(comment.upvotes);
    const [hasVoted, setHasVoted] = useState(false);

    const handleUpvote = async () => {
        if (hasVoted) return;

        try {
            const result = await api.upvoteComment(comment.id);
            setUpvotes(result.upvotes);
            setHasVoted(true);
        } catch {
            // Ignore - likely already voted
            setHasVoted(true);
        }
    };

    const handleReplySubmit = () => {
        setShowReplyForm(false);
        onRefresh();
    };

    const formatDate = (dateStr: string) => {
        const date = new Date(dateStr);
        return date.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    // Cap visual indentation at max_depth, but allow unlimited replies
    const visualDepth = Math.min(depth, config.max_depth);

    const scrollToParent = () => {
        if (!comment.parent_id) return;
        const parentEl = document.querySelector(
            `[data-comment-id="${comment.parent_id}"]`,
        );
        if (parentEl) {
            parentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            parentEl.classList.add('marge-comment-highlight');
            setTimeout(() => {
                parentEl.classList.remove('marge-comment-highlight');
            }, 2000);
        }
    };

    return (
        <div
            className="marge-comment"
            data-depth={visualDepth}
            data-comment-id={comment.id}
        >
            <div className="marge-comment-header">
                <img
                    src={comment.avatar}
                    alt=""
                    className="marge-avatar"
                    loading="lazy"
                />
                <div className="marge-comment-meta">
                    <span className="marge-author">
                        {comment.website ? (
                            <a
                                href={comment.website}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {comment.author || 'Anonymous'}
                            </a>
                        ) : (
                            comment.author || 'Anonymous'
                        )}
                        {comment.is_admin && (
                            <span className="marge-badge marge-badge-admin">
                                Admin
                            </span>
                        )}
                        {comment.email_verified && (
                            <span
                                className="marge-badge marge-badge-verified"
                                title="Verified email"
                            >
                                ✓
                            </span>
                        )}
                    </span>
                    <span className="marge-date">
                        {formatDate(comment.created_at)}
                    </span>
                    {comment.parent_author &&
                        visualDepth >= config.max_depth && (
                            <button
                                type="button"
                                className="marge-reply-to"
                                onClick={scrollToParent}
                            >
                                ↩ {comment.parent_author}
                            </button>
                        )}
                </div>
            </div>

            <div
                className="marge-comment-body"
                // biome-ignore lint/security/noDangerouslySetInnerHtml: HTML is sanitized server-side
                dangerouslySetInnerHTML={{ __html: comment.body_html }}
            />

            <div className="marge-comment-actions">
                <button
                    type="button"
                    className={`marge-action ${hasVoted ? 'marge-action-voted' : ''}`}
                    onClick={handleUpvote}
                    disabled={hasVoted}
                >
                    <svg
                        className="marge-upvote-icon"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path d="M12 4l-8 8h5v8h6v-8h5z" />
                    </svg>
                    <span>{upvotes}</span>
                </button>

                <button
                    type="button"
                    className="marge-action"
                    onClick={() => setShowReplyForm(!showReplyForm)}
                >
                    {showReplyForm ? 'Cancel' : 'Reply'}
                </button>
            </div>

            {showReplyForm && (
                <div className="marge-reply-form">
                    <CommentForm
                        api={api}
                        config={config}
                        uri={uri}
                        parentId={comment.id}
                        onSubmit={handleReplySubmit}
                        onCancel={() => setShowReplyForm(false)}
                    />
                </div>
            )}

            {comment.replies.length > 0 && (
                <div className="marge-replies">
                    {comment.replies.map((reply) => (
                        <Comment
                            key={reply.id}
                            comment={reply}
                            api={api}
                            config={config}
                            uri={uri}
                            depth={depth + 1}
                            onRefresh={onRefresh}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
