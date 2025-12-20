let listenersAttached = false;

const getBreadReadConfig = () => {
    const configEl = document.getElementById('voyager-bread-read-config');
    if (!configEl) return null;
    try {
        return JSON.parse(configEl.textContent || '{}');
    } catch (error) {
        console.error('[Voyager] Failed to parse bread-read config', error);
        return null;
    }
};

export const initBreadRead = () => {
    const config = getBreadReadConfig();
    if (!config) return;

    if (config.isModelTranslatable && window.VoyagerInitMultilingual) {
        window.VoyagerInitMultilingual(document.querySelectorAll('.side-body'));
    }

    if (listenersAttached) return;
    listenersAttached = true;
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initBreadRead());
};

