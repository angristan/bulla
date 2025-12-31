import { useCallback } from 'preact/hooks';

interface GitHubLoginButtonProps {
    authUrl: string;
    onSuccess: () => void;
}

export default function GitHubLoginButton({
    authUrl,
    onSuccess,
}: GitHubLoginButtonProps) {
    const handleClick = useCallback(() => {
        // Calculate popup position (centered)
        const width = 600;
        const height = 700;
        const left = window.screenX + (window.outerWidth - width) / 2;
        const top = window.screenY + (window.outerHeight - height) / 2;

        // Open popup
        const popup = window.open(
            authUrl,
            'github-oauth',
            `width=${width},height=${height},left=${left},top=${top},popup=yes`,
        );

        if (!popup) {
            // Popup was blocked, fall back to redirect
            window.location.href = authUrl;
            return;
        }

        // Listen for postMessage from callback page (may work in some browsers)
        const handleMessage = (event: MessageEvent) => {
            if (event.data?.type === 'marge-github-auth') {
                window.removeEventListener('message', handleMessage);
                clearInterval(checkClosed);
                popup?.close();
                // Always refresh config - the session has the commenter data
                onSuccess();
            }
        };

        window.addEventListener('message', handleMessage);

        // Poll for popup close - when closed, refresh config
        // The session will have commenter data if auth succeeded
        const checkClosed = setInterval(() => {
            if (popup?.closed) {
                clearInterval(checkClosed);
                window.removeEventListener('message', handleMessage);
                // Always call onSuccess to refresh config from server
                // The server session will have commenter data if auth succeeded
                onSuccess();
            }
        }, 500);
    }, [authUrl, onSuccess]);

    return (
        <button
            type="button"
            className="marge-btn marge-btn-github"
            onClick={handleClick}
        >
            <svg
                className="marge-github-icon"
                viewBox="0 0 16 16"
                width="16"
                height="16"
                aria-hidden="true"
            >
                <path
                    fill="currentColor"
                    fillRule="evenodd"
                    d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"
                />
            </svg>
            Login with GitHub
        </button>
    );
}
