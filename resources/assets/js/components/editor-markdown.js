import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

export const initMarkdownEditor = () => {
    document.querySelectorAll('textarea.easymde').forEach((textarea) => {
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
