import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

export const initMarkdownEditor = (scope = document) => {
    const container = scope === document ? document : (scope instanceof Element ? scope : document);
    container.querySelectorAll('textarea.easymde').forEach((textarea) => {
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
