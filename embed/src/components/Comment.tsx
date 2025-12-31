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
    onRefresh: (newCommentId: number) => void;
    onConfigRefresh: () => void;
}

export default function Comment({
    comment,
    api,
    config,
    uri,
    depth,
    onRefresh,
    onConfigRefresh,
}: CommentProps) {
    const [showReplyForm, setShowReplyForm] = useState(false);
    const [upvotes, setUpvotes] = useState(comment.upvotes);
    const [downvotes, setDownvotes] = useState(comment.downvotes);
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

    const handleDownvote = async () => {
        if (hasVoted) return;

        try {
            const result = await api.downvoteComment(comment.id);
            setDownvotes(result.downvotes);
            setHasVoted(true);
        } catch {
            // Ignore - likely already voted
            setHasVoted(true);
        }
    };

    const handleReplySubmit = (newCommentId: number) => {
        setShowReplyForm(false);
        onRefresh(newCommentId);
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

            // Wait for scroll to finish before highlighting
            const onScrollEnd = () => {
                parentEl.classList.add('bulla-comment-highlight');
                setTimeout(() => {
                    parentEl.classList.remove('bulla-comment-highlight');
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
    };

    const copyDeepLink = (e: MouseEvent) => {
        e.preventDefault();
        const url = `${window.location.href.split('#')[0]}#comment-${comment.id}`;
        navigator.clipboard.writeText(url);
        // Update URL without triggering navigation
        window.history.replaceState(null, '', `#comment-${comment.id}`);
    };

    return (
        <div
            className="bulla-comment"
            id={`comment-${comment.id}`}
            data-depth={visualDepth}
            data-comment-id={comment.id}
        >
            <div className="bulla-comment-header">
                <img
                    src={comment.avatar}
                    alt=""
                    className="bulla-avatar"
                    loading="lazy"
                />
                <div className="bulla-comment-meta">
                    <span className="bulla-author">
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
                            <span className="bulla-badge bulla-badge-admin">
                                {config.admin_badge_label}
                            </span>
                        )}
                        {!comment.is_admin && comment.is_github_user && (
                            <a
                                href={
                                    comment.github_username
                                        ? `https://github.com/${comment.github_username}`
                                        : undefined
                                }
                                target="_blank"
                                rel="noopener noreferrer"
                                className="bulla-badge bulla-badge-github"
                                title={
                                    comment.github_username
                                        ? `Logged in with GitHub as @${comment.github_username}`
                                        : 'Logged in with GitHub'
                                }
                                aria-label={
                                    comment.github_username
                                        ? `View @${comment.github_username} on GitHub`
                                        : 'GitHub user'
                                }
                            >
                                <svg
                                    viewBox="0 0 16 16"
                                    width="12"
                                    height="12"
                                    fill="currentColor"
                                    role="img"
                                    aria-label="GitHub"
                                >
                                    <path
                                        fillRule="evenodd"
                                        d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"
                                    />
                                </svg>
                            </a>
                        )}
                    </span>
                    <span className="bulla-date">
                        <a
                            href={`#comment-${comment.id}`}
                            className="bulla-date-link"
                            onClick={copyDeepLink}
                            title="Copy link to this comment"
                        >
                            {formatDate(comment.created_at)}
                        </a>
                        {comment.parent_author &&
                            visualDepth >= config.max_depth && (
                                <>
                                    {' · '}
                                    <button
                                        type="button"
                                        className="bulla-reply-to"
                                        onClick={scrollToParent}
                                    >
                                        ↩ {comment.parent_author}
                                    </button>
                                </>
                            )}
                    </span>
                </div>
            </div>

            <div
                className="bulla-comment-body"
                // biome-ignore lint/security/noDangerouslySetInnerHtml: HTML is sanitized server-side
                dangerouslySetInnerHTML={{ __html: comment.body_html }}
            />

            <div className="bulla-comment-actions">
                {(config.enable_upvotes || config.enable_downvotes) && (
                    <div className="bulla-vote-group">
                        {config.enable_upvotes && (
                            <button
                                type="button"
                                className={`bulla-action ${hasVoted ? 'bulla-action-voted' : ''}`}
                                onClick={handleUpvote}
                                disabled={hasVoted}
                            >
                                <svg
                                    className="bulla-upvote-icon"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path d="M12 4l-8 8h5v8h6v-8h5z" />
                                </svg>
                                <span>{upvotes}</span>
                            </button>
                        )}
                        {config.enable_downvotes && (
                            <button
                                type="button"
                                className={`bulla-action ${hasVoted ? 'bulla-action-voted' : ''}`}
                                onClick={handleDownvote}
                                disabled={hasVoted}
                            >
                                <svg
                                    className="bulla-downvote-icon"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path d="M12 20l8-8h-5V4H9v8H4z" />
                                </svg>
                                <span>{downvotes}</span>
                            </button>
                        )}
                    </div>
                )}
                <button
                    type="button"
                    className="bulla-action"
                    onClick={() => setShowReplyForm(!showReplyForm)}
                >
                    <svg
                        className="bulla-reply-icon"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        {showReplyForm ? (
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                        ) : (
                            <path d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z" />
                        )}
                    </svg>
                    <span>{showReplyForm ? 'Cancel' : 'Reply'}</span>
                </button>
            </div>

            {showReplyForm && (
                <div className="bulla-reply-form">
                    <CommentForm
                        api={api}
                        config={config}
                        uri={uri}
                        parentId={comment.id}
                        onSubmit={handleReplySubmit}
                        onConfigRefresh={onConfigRefresh}
                    />
                </div>
            )}

            {comment.replies.length > 0 && (
                <div className="bulla-replies">
                    {comment.replies.map((reply) => (
                        <Comment
                            key={reply.id}
                            comment={reply}
                            api={api}
                            config={config}
                            uri={uri}
                            depth={depth + 1}
                            onRefresh={onRefresh}
                            onConfigRefresh={onConfigRefresh}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
