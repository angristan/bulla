export interface Comment {
    id: number;
    parent_id: number | null;
    parent_author: string | null;
    depth: number;
    author: string | null;
    is_admin: boolean;
    is_github_user: boolean;
    github_username: string | null;
    avatar: string;
    website: string | null;
    body_html: string;
    upvotes: number;
    downvotes: number;
    created_at: string;
    replies: Comment[];
}

export interface Commenter {
    github_id: string;
    github_username: string | null;
    name: string | null;
    email: string | null;
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
    is_admin: boolean;
    enable_upvotes: boolean;
    enable_downvotes: boolean;
    github_auth_enabled: boolean;
    commenter: Commenter | null;
}

export interface CreateCommentResponse {
    id: number;
    author: string | null;
    is_admin: boolean;
    is_github_user: boolean;
    avatar: string;
    website: string | null;
    body_html: string;
    status: string;
    upvotes: number;
    downvotes: number;
    created_at: string;
    edit_token: string;
    edit_token_expires_at: string;
}

class Api {
    private baseUrl: string;
    private guest: boolean;

    constructor(baseUrl: string, guest = false) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
        this.guest = guest;
    }

    async getConfig(): Promise<Config> {
        const url = this.guest
            ? `${this.baseUrl}/api/config?guest=1`
            : `${this.baseUrl}/api/config`;
        const response = await fetch(url, {
            credentials: 'include',
        });
        if (!response.ok) throw new Error('Failed to load config');
        return response.json();
    }

    async getComments(
        uri: string,
        sort?: 'oldest' | 'newest' | 'popular',
    ): Promise<ThreadResponse> {
        const encoded = encodeURIComponent(uri);
        const params = sort ? `?sort=${sort}` : '';
        const response = await fetch(
            `${this.baseUrl}/api/threads/${encoded}/comments${params}`,
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
                credentials: 'include',
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

    async downvoteComment(commentId: number): Promise<{ downvotes: number }> {
        const response = await fetch(
            `${this.baseUrl}/api/comments/${commentId}/downvote`,
            {
                method: 'POST',
            },
        );
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to downvote');
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

    async logout(): Promise<void> {
        await fetch(`${this.baseUrl}/auth/logout`, {
            method: 'POST',
            credentials: 'include',
        });
    }

    getGitHubAuthUrl(): string {
        return `${this.baseUrl}/auth/github/redirect`;
    }
}

export default Api;
