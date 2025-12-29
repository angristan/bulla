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
            : options.container || document.getElementById('opaska-thread');

    if (!container) {
        console.error('[Opaska] Container element not found');
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
    const script = document.currentScript as HTMLScriptElement;
    if (!script) return;

    const baseUrl = script.dataset.opaska;
    if (!baseUrl) return;

    const theme = script.dataset.opaskaTheme as
        | 'light'
        | 'dark'
        | 'auto'
        | undefined;

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            init({ baseUrl, theme });
        });
    } else {
        init({ baseUrl, theme });
    }
}

// Run auto-init
autoInit();

// Export for manual initialization
export { init };
