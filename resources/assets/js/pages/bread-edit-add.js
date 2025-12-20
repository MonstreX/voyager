import { getCsrfToken } from '../modules/csrf';
import { showModal, hideModal } from '../core/bootstrap-compat';

let listenersAttached = false;

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
        window.VoyagerInitSlugify(document.querySelectorAll('.side-body input[data-slug-origin]'));
    }
    if (typeof window.VoyagerInitTooltips === 'function') {
        window.VoyagerInitTooltips(document.querySelectorAll('[data-toggle="tooltip"]'));
    }
};

const findSibling = (container, selector) => {
    if (!container) return null;
    return Array.from(container.children).find((child) => child.matches(selector)) || null;
};

export const initBreadEditAdd = () => {
    const config = getEditAddConfig();
    if (!config || !config.slug || !config.mediaRemoveUrl) return;

    assignDefaults(config);

    if (listenersAttached) return;
    listenersAttached = true;

    const confirmDeleteModal = document.getElementById('confirm_delete_modal');
    const confirmDeleteButton = document.getElementById('confirm_delete');
    const confirmDeleteName = confirmDeleteModal ? confirmDeleteModal.querySelector('.confirm_delete_name') : null;

    const deleteState = {
        params: {},
        wrapper: null,
    };

    const startDeleteFlow = (trigger, selector, isMulti) => {
        const container = trigger.parentElement;
        const fileNode = findSibling(container, selector);
        if (!container || !fileNode) return;

        deleteState.params = {
            slug: config.slug,
            filename: fileNode.dataset.fileName || '',
            id: fileNode.dataset.id || '',
            field: container.dataset.fieldName || '',
            multi: isMulti,
            _token: getCsrfToken(),
        };
        deleteState.wrapper = container;

        if (confirmDeleteName) {
            confirmDeleteName.textContent = deleteState.params.filename || '';
        }
        showModal(confirmDeleteModal);
    };

    const registerRemovalHandler = (selector, targetTag, isMulti) => {
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest(selector);
            if (!trigger) return;
            event.preventDefault();
            startDeleteFlow(trigger, targetTag, isMulti);
        });
    };

    [
        { selector: '.remove-multi-image', tag: 'img', multi: true },
        { selector: '.remove-single-image', tag: 'img', multi: false },
        { selector: '.remove-multi-file', tag: 'a', multi: true },
        { selector: '.remove-single-file', tag: 'a', multi: false },
    ].forEach(({ selector, tag, multi }) => registerRemovalHandler(selector, tag, multi));

    if (!confirmDeleteButton) return;

    confirmDeleteButton.addEventListener('click', () => {
        const formData = new URLSearchParams();
        Object.keys(deleteState.params).forEach((key) => {
            const value = deleteState.params[key];
            formData.append(key, typeof value === 'boolean' ? Number(value).toString() : value);
        });

        fetch(config.mediaRemoveUrl, {
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
                    const wrapper = deleteState.wrapper;
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
            })
            .finally(() => {
                hideModal(confirmDeleteModal);
            });
    });
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initBreadEditAdd());
};

