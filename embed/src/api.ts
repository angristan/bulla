export interface Comment {
    id: number;
    parent_id: number | null;
    author: string | null;
    email_verified: boolean;
    is_admin: boolean;
    avatar: string;
    website: string | null;
    body_html: string;
    upvotes: number;
    created_at: string;
    replies: Comment[];
}

export interface ThreadResponse {
    thread: {
        id: number;
        uri: string;
        title: string | null;
    };
    comments: Comment[];
    total: number;
}

export interface Config {
    site_name: string;
    require_author: boolean;
    require_email: boolean;
    moderation_mode: string;
    max_depth: number;
    edit_window_minutes: number;
    timestamp: string;
}

export interface CreateCommentResponse {
    id: number;
    author: string | null;
    email_verified: boolean;
    is_admin: boolean;
    avatar: string;
    website: string | null;
    body_html: string;
    status: string;
    upvotes: number;
    created_at: string;
    edit_token: string;
    edit_token_expires_at: string;
}

class Api {
    private baseUrl: string;

    constructor(baseUrl: string) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
    }

    async getConfig(): Promise<Config> {
        const response = await fetch(`${this.baseUrl}/api/config`);
        if (!response.ok) throw new Error('Failed to load config');
        return response.json();
    }

    async getComments(uri: string): Promise<ThreadResponse> {
        const encoded = encodeURIComponent(uri);
        const response = await fetch(
            `${this.baseUrl}/api/threads/${encoded}/comments`,
        );
        if (!response.ok) throw new Error('Failed to load comments');
        return response.json();
    }

    async createComment(
        uri: string,
        data: {
            parent_id?: number | null;
            author?: string;
            email?: string;
            website?: string;
            body: string;
            notify_replies?: boolean;
            title?: string;
            url?: string;
            honeypot?: string;
            timestamp?: string;
        },
    ): Promise<CreateCommentResponse> {
        const encoded = encodeURIComponent(uri);
        const response = await fetch(
            `${this.baseUrl}/api/threads/${encoded}/comments`,
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            },
        );
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to create comment');
        }
        return response.json();
    }

    async upvoteComment(commentId: number): Promise<{ upvotes: number }> {
        const response = await fetch(
            `${this.baseUrl}/api/comments/${commentId}/upvote`,
            {
                method: 'POST',
            },
        );
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to upvote');
        }
        return response.json();
    }

    async previewMarkdown(body: string): Promise<{ html: string }> {
        const response = await fetch(`${this.baseUrl}/api/comments/preview`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ body }),
        });
        if (!response.ok) throw new Error('Failed to preview');
        return response.json();
    }
}

export default Api;
