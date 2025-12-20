import { getCsrfToken } from '../modules/csrf';
import { showModal, hideModal } from '../core/bootstrap-compat';

let listenersAttached = false;

const getToastr = () => window.toastr || (window.Voyager && window.Voyager.toastr) || null;

const getMenuBuilderConfig = () => {
    if (typeof document === 'undefined') return null;
    const el = document.getElementById('voyager-menu-builder-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[VoyagerMenuBuilder] Failed to parse config', error);
        return null;
    }
};

const postForm = (url, params) => {
    if (window.Voyager && window.Voyager.http && typeof window.Voyager.http.postFormUrlEncoded === 'function') {
        return window.Voyager.http.postFormUrlEncoded(url, params);
    }
    const body = params instanceof URLSearchParams ? params : new URLSearchParams(params || {});
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body.toString(),
        credentials: 'same-origin',
    });
};

const scheduleToggleRefresh = (() => {
    let pending = false;
    return (input) => {
        if (!input || typeof window.VoyagerRefreshToggle !== 'function') return;
        if (pending) return;
        pending = true;
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                pending = false;
                window.VoyagerRefreshToggle(input);
            });
        });
    };
})();

const safeJsonParse = (value) => {
    if (!value || value === 'null') return null;
    try {
        return JSON.parse(value);
    } catch {
        return null;
    }
};

export const initMenusBuilder = () => {
    const config = getMenuBuilderConfig();
    if (!config) return;

    const menuModal = document.getElementById('menu_item_modal');
    const deleteModal = document.getElementById('delete_modal');
    const menuForm = document.getElementById('m_form');
    const formMethodInput = document.getElementById('m_form_method');

    if (!menuModal || !menuForm) return;

    const addLabel = config.labels && config.labels.add ? config.labels.add : 'Add';
    const updateLabel = config.labels && config.labels.update ? config.labels.update : 'Update';

    const idInput = document.getElementById('m_id');
    const titleInput = document.getElementById('m_title');
    const titleTranslationsInput = document.getElementById('title_i18n');
    const urlInput = document.getElementById('m_url');
    const routeInput = document.getElementById('m_route');
    const paramsInput = document.getElementById('m_parameters');
    const iconInput = document.getElementById('m_icon_class');
    const colorInput = document.getElementById('m_color');
    const targetSelect = document.getElementById('m_target');
    const statusInput = document.getElementById('m_status');
    const linkTypeSelect = document.getElementById('m_link_type');
    const urlTypeContainer = document.getElementById('m_url_type');
    const routeTypeContainer = document.getElementById('m_route_type');
    const addHeading = document.getElementById('m_hd_add');
    const editHeading = document.getElementById('m_hd_edit');
    const submitButton = menuForm.querySelector('input[type="submit"]');

    const deleteForm = document.getElementById('delete_form');
    const deleteActionTemplate = deleteForm ? deleteForm.getAttribute('action') : '';
    const deleteConfirmButton = document.getElementById('delete_confirm_button');

    let modalMultilingualInstance = null;

    const prepareHeading = (element) => {
        if (!element) return;
        element.classList.remove('hidden');
        element.style.display = 'none';
    };

    prepareHeading(addHeading);
    prepareHeading(editHeading);

    const toggleModalHeading = (isAdd) => {
        if (addHeading) addHeading.style.display = isAdd ? '' : 'none';
        if (editHeading) editHeading.style.display = isAdd ? 'none' : '';
    };

    const setLinkType = (type) => {
        if (!linkTypeSelect) return;
        linkTypeSelect.value = type;
        if (linkTypeSelect.value === 'route') {
            if (urlTypeContainer) urlTypeContainer.style.display = 'none';
            if (routeTypeContainer) routeTypeContainer.style.display = '';
        } else {
            if (urlTypeContainer) urlTypeContainer.style.display = '';
            if (routeTypeContainer) routeTypeContainer.style.display = 'none';
        }
    };

    const resetFormValues = () => {
        menuForm.reset();
        if (paramsInput) paramsInput.value = '';
        if (titleTranslationsInput) titleTranslationsInput.value = '';
        if (modalMultilingualInstance && typeof modalMultilingualInstance.refresh === 'function') {
            modalMultilingualInstance.refresh();
        }
    };

    const openAddModal = () => {
        resetFormValues();
        menuForm.setAttribute('action', menuForm.dataset.actionAdd);
        if (formMethodInput) formMethodInput.value = 'POST';
        if (submitButton) submitButton.value = addLabel;
        if (statusInput) {
            statusInput.checked = true;
            statusInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        toggleModalHeading(true);
        setLinkType('url');
        if (targetSelect) targetSelect.value = '_self';
        showModal(menuModal);
        scheduleToggleRefresh(statusInput);
    };

    const openEditModal = (button) => {
        if (!button) return;
        resetFormValues();
        menuForm.setAttribute('action', menuForm.dataset.actionUpdate);
        if (formMethodInput) formMethodInput.value = 'PUT';
        if (submitButton) submitButton.value = updateLabel;
        toggleModalHeading(false);

        const id = button.dataset.id || '';
        if (idInput) idInput.value = id;
        if (titleInput) titleInput.value = button.dataset.title || '';

        if (titleTranslationsInput) {
            const translationSource = document.getElementById('title' + id + '_i18n');
            titleTranslationsInput.value = translationSource ? translationSource.value : '';
        }

        if (modalMultilingualInstance && typeof modalMultilingualInstance.refresh === 'function') {
            modalMultilingualInstance.refresh();
        }

        if (targetSelect) targetSelect.value = button.dataset.target || '_self';

        const routeValue = button.dataset.route || '';
        const urlValue = button.dataset.url || '';
        setLinkType(routeValue ? 'route' : 'url');
        if (urlInput) urlInput.value = urlValue;
        if (routeInput) routeInput.value = routeValue;

        if (paramsInput) {
            const parsed = safeJsonParse(button.dataset.parameters);
            paramsInput.value = parsed ? JSON.stringify(parsed, null, 2) : (button.dataset.parameters || '');
        }

        if (iconInput) iconInput.value = button.dataset.icon_class || '';
        if (colorInput) colorInput.value = button.dataset.color || '';

        if (statusInput) {
            statusInput.checked = String(button.dataset.status || '1') !== '0';
            statusInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        showModal(menuModal);
        scheduleToggleRefresh(statusInput);
    };

    const openDeleteModal = (button) => {
        if (!deleteModal || !deleteForm || !deleteActionTemplate || !button) return;
        const id = button.dataset.id;
        if (id) {
            deleteForm.setAttribute('action', deleteActionTemplate.replace('__id', id));
        }
        showModal(deleteModal);
    };

    const initMultilingualSections = () => {
        if (!config.isModelTranslatable) return;
        if (!window.VoyagerInitMultilingual) return;

        window.VoyagerInitMultilingual('.side-body', {
            transInputs: '.dd-list input[data-i18n=true]',
        });

        const modalInstance = window.VoyagerInitMultilingual('#menu_item_modal', {
            form: 'form',
            transInputs: '#menu_item_modal input[data-i18n=true]',
            langSelectors: '#menu_item_modal .language-selector input',
            editing: true,
        });
        modalMultilingualInstance = Array.isArray(modalInstance) ? modalInstance[0] : modalInstance;
    };

    initMultilingualSections();

    if (!listenersAttached) {
        listenersAttached = true;

        document.querySelectorAll('.add_item').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                openAddModal();
            });
        });

        document.addEventListener('click', (event) => {
            const editButton = event.target.closest('.item_actions .edit');
            if (editButton) {
                event.preventDefault();
                openEditModal(editButton);
                return;
            }

            const deleteButton = event.target.closest('.item_actions .delete');
            if (deleteButton) {
                event.preventDefault();
                openDeleteModal(deleteButton);
            }
        });

        if (deleteConfirmButton) {
            deleteConfirmButton.addEventListener('click', () => {
                if (!deleteForm) return;
                deleteForm.submit();
            });
        }

        if (linkTypeSelect) {
            linkTypeSelect.addEventListener('change', () => {
                setLinkType(linkTypeSelect.value);
            });
        }

        if (menuModal) {
            menuModal.addEventListener('shown.bs.modal', () => {
                scheduleToggleRefresh(statusInput);
            });
        }

        document.addEventListener('click', (event) => {
            const statusToggle = event.target.closest('.tree-admin-status .voyager-status-toggle');
            if (!statusToggle) return;
            event.preventDefault();

            const id = statusToggle.dataset.id;
            if (!id) return;

            const currentValue = parseInt(statusToggle.dataset.value || '0', 10);
            const newValue = currentValue ? 0 : 1;

            statusToggle.dataset.value = String(newValue);
            statusToggle.classList.toggle('active', !!newValue);
            statusToggle.classList.toggle('inactive', !newValue);

            const itemLi = statusToggle.closest('li.dd-item');
            if (itemLi) itemLi.classList.toggle('unpublished-record', !newValue);

            const handleEl = statusToggle.closest('.dd-handle');
            const editButton = handleEl ? handleEl.querySelector('.item_actions .edit') : null;
            if (editButton) editButton.dataset.status = String(newValue);

            const payload = new URLSearchParams();
            payload.append('_token', getCsrfToken());
            payload.append('status', String(newValue));

            postForm(config.urls.statusTemplate.replace('__id', id), payload)
                .then((response) => response.text().then((text) => ({ response, text })))
                .then(({ response, text }) => {
                    let json = null;
                    try {
                        json = text ? JSON.parse(text) : null;
                    } catch {
                        json = null;
                    }

                    if (!response.ok || (json && json.status === 'error')) {
                        const message = json && json.message ? json.message : `Menu status update failed with status ${response.status}`;
                        throw new Error(message);
                    }

                    const toastr = getToastr();
                    toastr && toastr.success(config.i18n.successfullyUpdated || 'Updated');
                })
                .catch((error) => {
                    console.error('[VoyagerMenuBuilder] status update failed', error);

                    statusToggle.dataset.value = String(currentValue);
                    statusToggle.classList.toggle('active', !!currentValue);
                    statusToggle.classList.toggle('inactive', !currentValue);
                    if (itemLi) itemLi.classList.toggle('unpublished-record', !currentValue);
                    if (editButton) editButton.dataset.status = String(currentValue);

                    const toastr = getToastr();
                    toastr && toastr.error(error && error.message ? error.message : (config.i18n.internalError || 'Internal error'));
                });
        });

        const menuNestable = document.querySelector('.dd');
        if (menuNestable && window.VoyagerInitNestable) {
            window.VoyagerInitNestable(menuNestable, { handle: '.dd-tree-handle' });
            menuNestable.addEventListener('voyager.sortable.updated', (event) => {
                const structure = event.detail && event.detail.structure
                    ? event.detail.structure
                    : (window.VoyagerSerializeNestable ? window.VoyagerSerializeNestable(menuNestable) : []);

                const payload = new URLSearchParams();
                payload.append('order', JSON.stringify(structure));
                payload.append('_token', getCsrfToken());

                postForm(config.urls.order, payload)
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Menu order update failed with status ' + response.status);
                        }
                        const toastr = getToastr();
                        toastr && toastr.success(config.i18n.updatedOrder || 'Order updated');
                    })
                    .catch((error) => {
                        console.error('[VoyagerMenuBuilder] order update failed', error);
                        const toastr = getToastr();
                        toastr && toastr.error(config.i18n.internalError || 'Internal error');
                    });
            });

            if (!menuNestable.dataset.voyagerCollapseInitialized) {
                menuNestable.dataset.voyagerCollapseInitialized = 'true';
                menuNestable.addEventListener('click', (event) => {
                    const button = event.target.closest('button[data-action]');
                    if (!button) return;

                    const action = button.getAttribute('data-action');
                    if (action !== 'collapse' && action !== 'expand') return;

                    const item = button.closest('li.dd-item');
                    if (!item) return;

                    event.preventDefault();

                    const shouldCollapse = action === 'collapse';
                    item.classList.toggle('dd-collapsed', shouldCollapse);

                    const collapseButton = item.querySelector(':scope > button[data-action="collapse"]');
                    const expandButton = item.querySelector(':scope > button[data-action="expand"]');

                    if (collapseButton) {
                        collapseButton.style.display = shouldCollapse ? 'none' : '';
                    }
                    if (expandButton) {
                        expandButton.style.display = shouldCollapse ? '' : 'none';
                    }
                });
            }
        }
    }
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initMenusBuilder());
};
