let initInProgress = false;

const parseOptions = (value) => {
    if (!value) return {};
    if (typeof value === 'object') return value;
    try {
        return JSON.parse(value);
    } catch {
        return {};
    }
};

const resolveEditorsApi = (module) => {
    if (module && (typeof module.initJodit === 'function' || typeof module.initAceEditors === 'function')) {
        return module;
    }
    if (window.Voyager && window.Voyager.editors) {
        return window.Voyager.editors;
    }
    return null;
};

export const initRichTextBoxes = () => {
    if (typeof document === 'undefined') return;
    const elements = Array.from(document.querySelectorAll('textarea.richTextBox[data-voyager-rich-text="true"]'));
    if (!elements.length) return;

    if (!window.Voyager || typeof window.Voyager.loadEditors !== 'function') {
        return;
    }

    if (initInProgress) return;
    initInProgress = true;

    window.Voyager.loadEditors()
        .then((module) => {
            const api = resolveEditorsApi(module);
            const initJodit = api && typeof api.initJodit === 'function' ? api.initJodit : null;
            if (!initJodit) {
                throw new Error('initJodit unavailable');
            }

            elements.forEach((textarea) => {
                const id = textarea.id;
                if (!id) return;
                if (textarea.jodit || textarea.closest('.jodit-container')) {
                    return;
                }

                const options = parseOptions(textarea.dataset.joditOptions);
                options.type_slug = textarea.dataset.typeSlug || options.type_slug || '';
                options.upload_url = textarea.dataset.uploadUrl || options.upload_url || '';

                initJodit(`#${CSS.escape(id)}`, options);
            });
        })
        .catch((error) => {
            console.error('[VoyagerRichTextBox] init failed', error);
        })
        .finally(() => {
            initInProgress = false;
        });
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initRichTextBoxes());
};

