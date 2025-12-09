import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

const MARKDOWN_SELECTOR = 'textarea.easymde';

const normalizeScopeToRoots = (scope) => {
    if (!scope || scope === document) {
        return [document];
    }
    if (typeof scope === 'string') {
        return Array.from(document.querySelectorAll(scope));
    }
    if (scope instanceof Element || scope instanceof DocumentFragment) {
        return [scope];
    }
    if (scope instanceof NodeList || Array.isArray(scope)) {
        return Array.from(scope).filter((node) => node instanceof Element || node instanceof DocumentFragment);
    }
    return [];
};

const resolveMarkdownTargets = (scope) => {
    const targets = new Set();
    const roots = normalizeScopeToRoots(scope);
    if (!roots.length) {
        document.querySelectorAll(MARKDOWN_SELECTOR).forEach((textarea) => targets.add(textarea));
        return Array.from(targets);
    }

    roots.forEach((root) => {
        if (root === document) {
            document.querySelectorAll(MARKDOWN_SELECTOR).forEach((textarea) => targets.add(textarea));
            return;
        }
        if (!(root instanceof Element || root instanceof DocumentFragment)) {
            return;
        }
        if (root instanceof Element && root.matches(MARKDOWN_SELECTOR)) {
            targets.add(root);
        }
        if (typeof root.querySelectorAll === 'function') {
            root.querySelectorAll(MARKDOWN_SELECTOR).forEach((textarea) => targets.add(textarea));
        }
    });

    return Array.from(targets);
};

export const initMarkdownEditor = (scope = document) => {
    const textareas = resolveMarkdownTargets(scope);
    textareas.forEach((textarea) => {
        if (textarea.dataset.voyagerEasymdeInit === 'true') {
            return;
        }
        textarea.dataset.voyagerEasymdeInit = 'true';
        new EasyMDE({
            element: textarea,
            forceSync: true,
            spellChecker: false
        });
    });
};

/**
 * Subscribe to dom:updated event for automatic reinitialization
 * @param {Object} voyagerEvents - Event bus instance
 */
export const subscribeToEvents = (voyagerEvents) => {
    voyagerEvents.on('dom:updated', (container) => {
        initMarkdownEditor(container);
    });
};
