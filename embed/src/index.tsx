import { render } from 'preact';
import App from './components/App';
import './styles/embed.css';

export type SortOrder = 'oldest' | 'newest' | 'popular';

interface MargeOptions {
    baseUrl: string;
    uri?: string;
    pageTitle?: string;
    pageUrl?: string;
    theme?: 'light' | 'dark' | 'auto';
    container?: HTMLElement | string;
    guest?: boolean;
    sort?: SortOrder;
}

function init(options: MargeOptions) {
    const container =
        typeof options.container === 'string'
            ? document.querySelector(options.container)
            : options.container || document.getElementById('marge-thread');

    if (!container) {
        console.error('[Marge] Container element not found');
        return;
    }

    // Check container for data-uri attribute as fallback
    const uri =
        options.uri ||
        (container as HTMLElement).dataset.uri ||
        window.location.pathname;
    const pageTitle = options.pageTitle || document.title;
    const pageUrl = options.pageUrl || window.location.href;

    render(
        <App
            baseUrl={options.baseUrl}
            uri={uri}
            pageTitle={pageTitle}
            pageUrl={pageUrl}
            theme={options.theme}
            guest={options.guest}
            defaultSort={options.sort}
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

    const getTheme = () =>
        script.getAttribute('data-marge-theme') as
            | 'light'
            | 'dark'
            | 'auto'
            | null;
    const guest = script.getAttribute('data-marge-guest') === 'true';
    const uri = script.getAttribute('data-marge-uri') || undefined;
    const sortAttr = script.getAttribute('data-marge-sort');
    const sort =
        sortAttr && ['oldest', 'newest', 'popular'].includes(sortAttr)
            ? (sortAttr as SortOrder)
            : undefined;

    const doInit = () => {
        const theme = getTheme();
        init({ baseUrl, theme: theme || undefined, guest, uri, sort });
    };

    // Watch for theme attribute changes and re-render
    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (
                mutation.type === 'attributes' &&
                mutation.attributeName === 'data-marge-theme'
            ) {
                doInit();
                break;
            }
        }
    });
    observer.observe(script, { attributes: true });

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', doInit);
    } else {
        doInit();
    }
}

// Run auto-init
autoInit();

// Export for manual initialization
export { init };
