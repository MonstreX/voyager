import { getCsrfToken } from '../modules/csrf';
import { showModal, hideModal } from '../core/bootstrap-compat';
import { getToastr } from '../core/toastr';

let listenersAttached = false;

const getBrowseListConfig = () => {
    const configEl = document.getElementById('voyager-browse-list-config');
    if (!configEl) return null;
    try {
        return JSON.parse(configEl.textContent || '{}');
    } catch (error) {
        console.error('[Voyager] Failed to parse browse-list config', error);
        return null;
    }
};

const postFormUrlEncoded = (url, params) => {
    const headers = {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
    };

    return fetch(url, {
        method: 'POST',
        body: params,
        headers
    });
};

const updateSelectedIdsInput = () => {
    const selectedInput = document.querySelector('.selected_ids');
    if (!selectedInput) return;

    const ids = Array.from(document.querySelectorAll('input[name="row_id"]:checked')).map((checkbox) => checkbox.value);
    selectedInput.value = ids.join(',');
};

const closeGroupModal = (modal) => {
    if (!modal) return;
    hideModal(modal);
    modal.dataset.recordId = '';
    modal.dataset.fieldName = '';
    modal.dataset.slug = '';
};

const ensureGroupModalDismissHandlers = (modal) => {
    if (!modal || modal.dataset.voyagerDismissInit === '1') return;
    modal.dataset.voyagerDismissInit = '1';

    modal.querySelectorAll('[data-dismiss="modal"]').forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            closeGroupModal(modal);
        });
    });
};

const initBrowseListOnce = () => {
    const config = getBrowseListConfig();
    if (!config) return;

    if (config.serverSide) {
        document.querySelectorAll('#search-input select').forEach((select) => {
            if (select.dataset.voyagerDisableSearch === 'true') return;
            select.dataset.voyagerDisableSearch = 'true';
            const refreshSelect = window.Voyager && window.Voyager.refresh && window.Voyager.refresh.select;
            if (typeof refreshSelect === 'function') {
                refreshSelect(select);
            }
        });
    }

    if (config.isModelTranslatable && window.VoyagerInitMultilingual) {
        window.VoyagerInitMultilingual('.side-body');
    }

    const groupModal = document.getElementById('group_inline_edit_modal');
    if (groupModal) {
        ensureGroupModalDismissHandlers(groupModal);
    }
};

export const initBreadBrowseList = () => {
    const config = getBrowseListConfig();
    if (!config) return;

    initBrowseListOnce();

    if (listenersAttached) return;
    listenersAttached = true;

    document.addEventListener('change', (event) => {
        const target = event.target;
        if (!target) return;

        if (target.matches('input[name="row_id"]')) {
            updateSelectedIdsInput();
            return;
        }

        if (target.id === 'show_soft_deletes' && config.usesSoftDeletes && config.softDeleteUrls) {
            const checked = !!target.checked;
            const nextUrl = checked ? config.softDeleteUrls.on : config.softDeleteUrls.off;
            if (nextUrl) {
                window.location.href = nextUrl;
            }
            return;
        }

        if (target.classList && target.classList.contains('filter-select')) {
            const holder = document.querySelector('.browse-filters-holder');
            const url = holder ? holder.dataset.url : '';
            if (!url) return;

            const filterSelects = Array.from(document.querySelectorAll('.filter-select'));
            const params = [];
            let index = 0;
            filterSelects.forEach((elem) => {
                if (!elem.value) return;
                params.push(`field[${index}]=${encodeURIComponent(elem.dataset.column || '')}`);
                params.push(`value[${index}]=${encodeURIComponent(elem.value)}`);
                index += 1;
            });

            if (params.length === 0) {
                window.location.href = `${url}?reset_filters`;
            } else {
                window.location.href = `${url}?${params.join('&')}`;
            }
        }
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!target) return;

        const selectAllToggle = target.closest('.select_all');
        if (selectAllToggle) {
            const checked = !!selectAllToggle.checked;
            document.querySelectorAll('input[name="row_id"]').forEach((checkbox) => {
                checkbox.checked = checked;
                checkbox.dispatchEvent(new Event('change'));
            });
            return;
        }

        const statusToggle = target.closest('.voyager-status-toggle');
        if (statusToggle && config.updateFieldUrlTemplate) {
            const id = statusToggle.dataset.id;
            const field = statusToggle.dataset.field;
            const slug = statusToggle.dataset.slug;
            const currentValue = parseInt(statusToggle.dataset.value, 10) || 0;
            const newValue = currentValue ? 0 : 1;
            const updateUrl = config.updateFieldUrlTemplate.replace('__id', id);

            const params = new URLSearchParams();
            params.append('field', field || '');
            params.append('value', String(newValue));
            params.append('slug', slug || '');
            params.append('_token', getCsrfToken());

            postFormUrlEncoded(updateUrl, params)
                .then((response) => response.json())
                .then((data) => {
                    const toastr = getToastr();
                    if (data.status === 'success') {
                        toastr && toastr.success(data.message);
                        statusToggle.dataset.value = String(newValue);
                        statusToggle.classList.toggle('active', !!newValue);
                        statusToggle.classList.toggle('inactive', !newValue);
                    } else {
                        toastr && toastr.error(data.message || 'Error updating field');
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    const toastr = getToastr();
                    toastr && toastr.error('Error updating field');
                });
            return;
        }

        const inlineEditBtn = target.closest('.text-inline-edit');
        if (inlineEditBtn) {
            const textHolder = inlineEditBtn.closest('.browse-text-holder');
            const fieldHolder = inlineEditBtn.closest('.text-field-holder');
            const editorHolder = fieldHolder ? fieldHolder.querySelector('.browse-inline-editor') : null;

            if (textHolder) textHolder.style.display = 'none';
            if (editorHolder) {
                editorHolder.style.display = 'flex';
                const input = editorHolder.querySelector('.browse-inline-input');
                if (input) input.focus();
            }
            return;
        }

        const inlineCancelBtn = target.closest('.text-inline-cancel');
        if (inlineCancelBtn) {
            const editorHolder = inlineCancelBtn.closest('.browse-inline-editor');
            const fieldHolder = inlineCancelBtn.closest('.text-field-holder');
            const textHolder = fieldHolder ? fieldHolder.querySelector('.browse-text-holder') : null;

            if (editorHolder) editorHolder.style.display = 'none';
            if (textHolder) textHolder.style.display = 'flex';
            return;
        }

        const inlineSaveBtn = target.closest('.text-inline-save');
        if (inlineSaveBtn && config.updateFieldUrlTemplate) {
            const editorHolder = inlineSaveBtn.closest('.browse-inline-editor');
            const input = editorHolder ? editorHolder.querySelector('.browse-inline-input') : null;
            if (!input) return;

            const fieldHolder = editorHolder.closest('.text-field-holder');
            const textHolder = fieldHolder ? fieldHolder.querySelector('.browse-text-holder') : null;

            const parentRow = inlineSaveBtn.closest('tr');
            const recordId = input.dataset.id;
            const fieldName = input.name;
            const newValue = input.value;
            const dataTypeSlug = parentRow ? parentRow.dataset.slug : '';

            if (editorHolder) editorHolder.style.display = 'none';
            if (textHolder) {
                textHolder.style.display = 'flex';
                const displayedText = textHolder.querySelector('div');
                if (displayedText) displayedText.textContent = newValue;
            }

            const updateUrl = config.updateFieldUrlTemplate.replace('__id', recordId);
            const params = new URLSearchParams();
            params.append('field', fieldName || '');
            params.append('value', newValue || '');
            params.append('slug', dataTypeSlug || '');
            params.append('_token', getCsrfToken());

            postFormUrlEncoded(updateUrl, params)
                .then((response) => response.json())
                .then((data) => {
                    const toastr = getToastr();
                    if (data.status === 'success') {
                        toastr && toastr.success(data.message);
                    } else {
                        toastr && toastr.error(data.message || 'Error updating field');
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    const toastr = getToastr();
                    toastr && toastr.error('Error updating field');
                });
            return;
        }

        const groupEditBtn = target.closest('.group-inline-edit');
        if (groupEditBtn && config.updateFieldUrlTemplate) {
            event.preventDefault();

            const fieldsContainer = groupEditBtn.closest('.browse-group-fields');
            const tr = groupEditBtn.closest('tr');
            if (!fieldsContainer || !tr) return;

            const recordId = tr.dataset.recordId || '';
            const slug = tr.dataset.slug || '';
            const fieldName = groupEditBtn.dataset.name || '';

            const groupModal = document.getElementById('group_inline_edit_modal');
            if (!groupModal) return;
            ensureGroupModalDismissHandlers(groupModal);

            const modalForm = groupModal.querySelector('.inline-group-form');
            if (!modalForm) return;

            modalForm.innerHTML = '';

            let groupData = null;
            try {
                const raw = groupEditBtn.getAttribute('data-group-data');
                groupData = raw ? JSON.parse(raw) : null;
            } catch (error) {
                console.error('Error parsing group data:', error);
            }

            const groupFields = fieldsContainer.querySelectorAll('.browse-group-field');
            const fieldsData = {};

            groupFields.forEach((field) => {
                const key = field.dataset.key;
                const fieldSpan = fieldsContainer.querySelector(`[data-key="${key}"]`);

                let value = '';
                let label = key;
                if (groupData && groupData.fields && groupData.fields[key]) {
                    value = groupData.fields[key].value || '';
                    label = groupData.fields[key].label || key;
                } else if (fieldSpan && fieldSpan.title) {
                    label = fieldSpan.title;
                }

                fieldsData[key] = { key, value, label };
            });

            Object.values(fieldsData).forEach((data) => {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.style.marginBottom = '10px';
                input.dataset.key = data.key;
                input.dataset.label = data.label;
                input.placeholder = data.label;
                input.value = data.value;
                modalForm.appendChild(input);
            });

            groupModal.dataset.recordId = recordId;
            groupModal.dataset.fieldName = fieldName;
            groupModal.dataset.slug = slug;
            showModal(groupModal);
            return;
        }

        const groupModalSaveBtn = target.closest('#group_inline_edit_modal .group-save-btn');
        if (groupModalSaveBtn && config.updateFieldUrlTemplate) {
            event.preventDefault();

            const groupModal = document.getElementById('group_inline_edit_modal');
            if (!groupModal) return;
            const modalForm = groupModal.querySelector('.inline-group-form');
            if (!modalForm) return;

            const currentRecordId = groupModal.dataset.recordId || '';
            const currentFieldName = groupModal.dataset.fieldName || '';
            const currentSlug = groupModal.dataset.slug || '';

            const inputs = Array.from(modalForm.querySelectorAll('input'));
            const data = { fields: {} };

            inputs.forEach((input) => {
                const key = input.dataset.key;
                data.fields[key] = {
                    type: 'text',
                    label: input.dataset.label || key,
                    value: input.value
                };

                const icon = input.value && input.value.length > 0 ? 'voyager-check' : 'voyager-dot';
                const actualFieldsContainer = document.querySelector(
                    `[data-record-id="${currentRecordId}"] .browse-group-fields[data-field-name="${currentFieldName}"]`
                );
                if (actualFieldsContainer) {
                    const fieldSpan = actualFieldsContainer.querySelector(`[data-key="${key}"]`);
                    if (fieldSpan) {
                        fieldSpan.innerHTML = `<i class="${icon}"></i>`;
                    }
                    return;
                }

                const actualRow = document.querySelector(`[data-record-id="${currentRecordId}"]`);
                if (!actualRow) return;

                const allGroupFields = actualRow.querySelectorAll('.browse-group-fields');
                allGroupFields.forEach((container) => {
                    const btn = container.querySelector(`[data-name="${currentFieldName}"]`);
                    if (!btn) return;
                    const fieldSpan = container.querySelector(`[data-key="${key}"]`);
                    if (fieldSpan) {
                        fieldSpan.innerHTML = `<i class="${icon}"></i>`;
                    }
                });
            });

            const updateUrl = config.updateFieldUrlTemplate.replace('__id', currentRecordId);
            const params = new URLSearchParams();
            params.append('field', currentFieldName);
            params.append('value', JSON.stringify(data));
            params.append('slug', currentSlug);
            params.append('_token', getCsrfToken());

            postFormUrlEncoded(updateUrl, params)
                .then((response) => response.json())
                .then((responseData) => {
                    const toastr = getToastr();
                    if (responseData.status === 'success') {
                        toastr && toastr.success(responseData.message);

                        const updatedGroupData = { fields: {} };
                        inputs.forEach((input) => {
                            const key = input.dataset.key;
                            updatedGroupData.fields[key] = {
                                type: 'text',
                                label: input.dataset.label || key,
                                value: input.value
                            };
                        });

                        const correctRow = document.querySelector(`[data-record-id="${currentRecordId}"]`);
                        if (correctRow) {
                            const editBtn = correctRow.querySelector(`[data-name="${currentFieldName}"]`);
                            if (editBtn) {
                                editBtn.setAttribute('data-group-data', JSON.stringify(updatedGroupData));
                            }
                        }

                        closeGroupModal(groupModal);
                    } else {
                        toastr && toastr.error(responseData.message || 'Error updating field');
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    const toastr = getToastr();
                    toastr && toastr.error('Error updating field');
                });
        }
    });

    document.addEventListener('keypress', (event) => {
        if (event.key !== 'Enter') return;
        const input = event.target;
        if (!input || !input.classList || !input.classList.contains('browse-inline-input')) return;
        const saveBtn = input.closest('.browse-inline-editor')?.querySelector('.text-inline-save');
        if (saveBtn) {
            saveBtn.click();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const groupModal = document.getElementById('group_inline_edit_modal');
        if (!groupModal) return;
        if (groupModal.style.display === 'block' || groupModal.classList.contains('in')) {
            closeGroupModal(groupModal);
        }
    });
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initBreadBrowseList());
};

