let listenersAttached = false;

const parseJsonConfig = () => {
    if (typeof document === 'undefined') return null;
    const el = document.getElementById('voyager-users-edit-add-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[VoyagerUsersEditAdd] Failed to parse config', error);
        return null;
    }
};

export const initUsersEditAdd = () => {
    if (typeof document === 'undefined') return;
    const config = parseJsonConfig();
    if (!config) return;

    if (!listenersAttached) {
        listenersAttached = true;
    }

    const init = window.Voyager && window.Voyager.init;
    if (init && typeof init.toggles === 'function') init.toggles();
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initUsersEditAdd());
};
