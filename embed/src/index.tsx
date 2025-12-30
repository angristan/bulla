import { render } from 'preact';
import App from './components/App';
import './styles/embed.css';

interface OapaskaOptions {
    baseUrl: string;
    uri?: string;
    pageTitle?: string;
    pageUrl?: string;
    theme?: 'light' | 'dark' | 'auto';
    container?: HTMLElement | string;
}

function init(options: OapaskaOptions) {
    const container =
        typeof options.container === 'string'
            ? document.querySelector(options.container)
            : options.container || document.getElementById('marge-thread');

    if (!container) {
        console.error('[Marge] Container element not found');
        return;
    }

    const uri = options.uri || window.location.pathname;
    const pageTitle = options.pageTitle || document.title;
    const pageUrl = options.pageUrl || window.location.href;

    render(
        <App
            baseUrl={options.baseUrl}
            uri={uri}
            pageTitle={pageTitle}
            pageUrl={pageUrl}
            theme={options.theme}
        />,
        container,
    );
}

// Auto-init from script attributes
function autoInit() {
    // document.currentScript is null for dynamically loaded scripts
    // Fall back to finding the script by its data attribute
    const script =
        (document.currentScript as HTMLScriptElement) ||
        document.querySelector('script[data-marge]');
    if (!script) return;

    const baseUrl = script.getAttribute('data-marge');
    if (!baseUrl) return;

    const theme = script.getAttribute('data-marge-theme') as
        | 'light'
        | 'dark'
        | 'auto'
        | null;

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            init({ baseUrl, theme: theme || undefined });
        });
    } else {
        init({ baseUrl, theme: theme || undefined });
    }
}

// Run auto-init
autoInit();

// Export for manual initialization
export { init };
