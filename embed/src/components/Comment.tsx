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

    return (
        <div className="opaska-comment" data-depth={depth}>
            <div className="opaska-comment-header">
                <img
                    src={comment.avatar}
                    alt=""
                    className="opaska-avatar"
                    loading="lazy"
                />
                <div className="opaska-comment-meta">
                    <span className="opaska-author">
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
                            <span className="opaska-badge opaska-badge-admin">
                                Admin
                            </span>
                        )}
                        {comment.email_verified && (
                            <span
                                className="opaska-badge opaska-badge-verified"
                                title="Verified email"
                            >
                                ✓
                            </span>
                        )}
                    </span>
                    <span className="opaska-date">
                        {formatDate(comment.created_at)}
                    </span>
                </div>
            </div>

            <div
                className="opaska-comment-body"
                // biome-ignore lint/security/noDangerouslySetInnerHtml: HTML is sanitized server-side
                dangerouslySetInnerHTML={{ __html: comment.body_html }}
            />

            <div className="opaska-comment-actions">
                <button
                    type="button"
                    className={`opaska-action ${hasVoted ? 'opaska-action-voted' : ''}`}
                    onClick={handleUpvote}
                    disabled={hasVoted}
                >
                    <span className="opaska-upvote-icon">▲</span>
                    <span>{upvotes}</span>
                </button>

                {depth < config.max_depth && (
                    <button
                        type="button"
                        className="opaska-action"
                        onClick={() => setShowReplyForm(!showReplyForm)}
                    >
                        {showReplyForm ? 'Cancel' : 'Reply'}
                    </button>
                )}
            </div>

            {showReplyForm && (
                <div className="opaska-reply-form">
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
                <div className="opaska-replies">
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
