import { getToastr } from '../core/toastr';

let listenersAttached = false;
let optionsEditorInstance = null;
let optionsEditorReady = false;

const parseJsonConfig = () => {
    if (typeof document === 'undefined') return null;
    const el = document.getElementById('voyager-settings-index-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[VoyagerSettings] Failed to parse config', error);
        return null;
    }
};

const ensureOptionsEditor = (config) => {
    if (optionsEditorReady) {
        if (optionsEditorInstance) {
            optionsEditorInstance.resize(true);
        }
        return;
    }

    const editorContainer = document.getElementById('options_editor');
    const optionsTextarea = document.getElementById('options_textarea');
    if (!editorContainer || !optionsTextarea) {
        return;
    }
    if (!window.Voyager || typeof window.Voyager.loadEditors !== 'function') {
        return;
    }

    window.Voyager.loadEditors()
        .then((module) => {
            const initAceEditors =
                module && typeof module.initAceEditors === 'function'
                    ? module.initAceEditors
                    : window.Voyager.editors && window.Voyager.editors.initAceEditors;

            if (!optionsEditorInstance && typeof initAceEditors === 'function') {
                initAceEditors(editorContainer.parentElement || document);
            }

            if (!optionsEditorInstance && typeof ace !== 'undefined') {
                optionsEditorInstance = ace.edit('options_editor');
                optionsEditorInstance.getSession().setMode('ace/mode/json');
                optionsEditorInstance.getSession().on('change', () => {
                    optionsTextarea.value = optionsEditorInstance.getValue();
                });
                optionsEditorReady = true;
            }

            if (optionsEditorInstance) {
                optionsEditorInstance.resize(true);
            }
        })
        .catch((error) => {
            console.error('[VoyagerSettings] Failed to initialize options editor', error);
            const toastr = getToastr();
            toastr && toastr.error(config?.i18n?.editorsInitFailed || 'Failed to initialize editor');
        });
};

const initOptionsToggle = (config) => {
    const toggleOptions = document.getElementById('toggle_options');
    if (!toggleOptions || toggleOptions.dataset.voyagerInit === 'true') {
        return;
    }
    toggleOptions.dataset.voyagerInit = 'true';

    toggleOptions.addEventListener('click', () => {
        document.querySelectorAll('.new-settings-options').forEach((section) => {
            const isHidden = window.getComputedStyle(section).display === 'none';
            section.style.display = isHidden ? 'block' : 'none';
        });
        const icon = toggleOptions.querySelector('.voyager-double-down, .voyager-double-up');
        if (icon) {
            icon.classList.toggle('voyager-double-down');
            icon.classList.toggle('voyager-double-up');
        }
        ensureOptionsEditor(config);
    });

    ensureOptionsEditor(config);
};

const initTabs = () => {
    if (!listenersAttached) {
        document.addEventListener('click', (event) => {
            const tabButton = event.target.closest('[data-toggle="tab"]');
            if (!tabButton) return;
            const label = (tabButton.textContent || '').trim();
            document.querySelectorAll('.setting_tab').forEach((input) => {
                input.value = label;
            });
        });
    }
};

const initDeleteValueLinks = () => {
    if (!listenersAttached) {
        document.addEventListener('click', (event) => {
            const link = event.target.closest('.delete_value');
            if (!link) return;
            event.preventDefault();

            const form = link.closest('form');
            if (!form) return;
            const href = link.getAttribute('href');
            if (!href) return;

            form.setAttribute('action', href);
            form.submit();
        });
    }
};

export const initSettingsIndex = () => {
    if (typeof document === 'undefined') return;
    const config = parseJsonConfig();
    if (!config) return;

    initOptionsToggle(config);

    if (!listenersAttached) {
        listenersAttached = true;
        initTabs();
        initDeleteValueLinks();
    }

    if (typeof window.VoyagerInitToggles === 'function') {
        window.VoyagerInitToggles();
    }
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initSettingsIndex());
};
