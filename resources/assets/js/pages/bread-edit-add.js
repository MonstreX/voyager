import { getCsrfToken } from '../modules/csrf';

let listenersAttached = false;

let currentConfig = null;

const getEditAddConfig = () => {
    const configEl = document.getElementById('voyager-edit-add-config');
    if (!configEl) return null;
    try {
        return JSON.parse(configEl.textContent || '{}');
    } catch (error) {
        console.error('[Voyager] Failed to parse edit-add config', error);
        return null;
    }
};

const getToastr = () => window.toastr || (window.Voyager && window.Voyager.toastr);

const assignDefaults = (config) => {
    if (typeof window.VoyagerInitToggles === 'function') {
        window.VoyagerInitToggles();
    }
    if (typeof window.VoyagerInitDatePickers === 'function') {
        window.VoyagerInitDatePickers();
    }
    if (config.isModelTranslatable && typeof window.VoyagerInitMultilingual === 'function') {
        window.VoyagerInitMultilingual(document.querySelectorAll('.side-body'), { editing: true });
    }
    if (typeof window.VoyagerInitSlugify === 'function') {
        const selector = config.slugifySelector || '.side-body input[data-slug-origin]';
        window.VoyagerInitSlugify(document.querySelectorAll(selector));
    }
    if (typeof window.VoyagerInitTooltips === 'function') {
        window.VoyagerInitTooltips(document.querySelectorAll('[data-toggle="tooltip"]'));
    }
};

const findSibling = (container, selector) => {
    if (!container) return null;
    return Array.from(container.children).find((child) => child.matches(selector)) || null;
};

const resolveRemoveContext = (trigger) => {
    if (!trigger || typeof document === 'undefined') return null;

    const isMulti =
        trigger.classList.contains('remove-multi-image') ||
        trigger.classList.contains('remove-multi-file');

    const isImage =
        trigger.classList.contains('remove-multi-image') ||
        trigger.classList.contains('remove-single-image');

    const container = trigger.closest('[data-field-name]') || trigger.parentElement;
    if (!container) return null;

    const selector = isImage ? 'img' : 'a.fileType';
    const fileNode = findSibling(container, selector) || container.querySelector(selector);
    if (!fileNode) return null;

    return {
        wrapper: container,
        params: {
            slug: currentConfig && currentConfig.slug ? currentConfig.slug : '',
            filename: fileNode.dataset.fileName || '',
            id: fileNode.dataset.id || '',
            field: container.dataset.fieldName || '',
            multi: isMulti,
            _token: getCsrfToken(),
        },
    };
};

const registerConfirmCallbacks = () => {
    if (typeof window === 'undefined') return;
    window.Voyager = window.Voyager || {};
    window.Voyager.confirmCallbacks = window.Voyager.confirmCallbacks || {};

    if (window.Voyager.confirmCallbacks.mediaRemove) {
        return;
    }

    window.Voyager.confirmCallbacks.mediaRemove = ({ trigger }) => {
        const context = resolveRemoveContext(trigger);
        if (!context || !currentConfig || !currentConfig.mediaRemoveUrl) {
            return true;
        }

        const formData = new URLSearchParams();
        Object.keys(context.params).forEach((key) => {
            const value = context.params[key];
            formData.append(key, typeof value === 'boolean' ? Number(value).toString() : value);
        });

        return fetch(currentConfig.mediaRemoveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: formData.toString(),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Media remove request failed with status ' + response.status);
                }
                return response.json();
            })
            .then((response) => {
                const toastr = getToastr();
                if (response && response.data && response.data.status && response.data.status == 200) {
                    toastr && toastr.success(response.data.message);
                    const wrapper = context.wrapper;
                    if (wrapper) {
                        wrapper.style.transition = 'opacity 0.3s ease';
                        wrapper.style.opacity = '0';
                        setTimeout(() => wrapper.remove(), 300);
                    }
                } else {
                    toastr && toastr.error('Error removing file.');
                }
            })
            .catch((error) => {
                console.error('Voyager media remove failed', error);
                const toastr = getToastr();
                toastr && toastr.error('Error removing file.');
            });
    };
};

export const initBreadEditAdd = () => {
    const config = getEditAddConfig();
    if (!config || !config.slug || !config.mediaRemoveUrl) return;

    currentConfig = config;
    assignDefaults(config);
    registerConfirmCallbacks();

    if (listenersAttached) return;
    listenersAttached = true;
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initBreadEditAdd());
};
