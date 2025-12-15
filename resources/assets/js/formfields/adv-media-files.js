import { showModal, hideModal } from '../core/bootstrap-compat';

const getCsrfToken = () => {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    return tokenMeta ? tokenMeta.getAttribute('content') : '';
};

const openModal = (modal) => {
    if (modal) {
        showModal(modal);
    }
};

const closeModal = (modal) => {
    if (modal) {
        hideModal(modal);
    }
};

const updateOrderInputs = (listEl) => {
    listEl.querySelectorAll('.adv-media-files-item').forEach((item, index) => {
        const orderLabel = item.querySelector('.adv-media-files-order');
        if (orderLabel) {
            orderLabel.textContent = String(index + 1);
        }
    });
};

const collectOrder = (listEl) => {
    const ids = [];
    listEl.querySelectorAll('.adv-media-files-item-holder').forEach((item, index) => {
        const mediaId = item.getAttribute('data-file-id');
        if (mediaId) {
            ids.push(mediaId);
        }
        const itemWrapper = item.closest('.adv-media-files-item');
        const orderLabel = itemWrapper ? itemWrapper.querySelector('.adv-media-files-order') : null;
        if (orderLabel) orderLabel.textContent = String(index + 1);
    });
    return ids;
};

const callReorder = (listEl) => {
    const reorderUrl = listEl.getAttribute('data-reorder-url');
    const modelType = listEl.getAttribute('data-model-type');
    const modelId = listEl.getAttribute('data-model-id');
    const collectionName = listEl.getAttribute('data-collection-name');
    if (!reorderUrl || !modelType || !modelId || !collectionName) {
        return;
    }
    const order = collectOrder(listEl);
    fetch(reorderUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            model_type: modelType,
            model_id: modelId,
            collection_name: collectionName,
            order,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (window.toastr && data && data.status === 'success') {
                window.toastr.success(data.message || 'Order updated');
            }
        })
        .catch(() => {});
};

const buildMediaUrl = (listEl, mediaId) => {
    const template = listEl.getAttribute('data-delete-url-template');
    if (template) {
        return template.replace('__MEDIA_ID__', mediaId);
    }
    const prefix = (window.voyagerPrefix || '/admin').replace(/\/?$/, '');
    return `${prefix}/api/media/${mediaId}`;
};

const buildUpdatePropsUrl = (mediaUrl) => `${mediaUrl.replace(/\/$/, '')}/props`;

const bindSortable = (listEl) => {
    if (typeof Sortable === 'undefined') {
        return;
    }
    Sortable.create(listEl, {
        animation: 200,
        sort: true,
        scroll: true,
        onStart: () => {
            listEl.__advMediaDragging = true;
            listEl.__advMediaSuppressClickUntil = Date.now() + 250;
        },
        onEnd: () => {
            updateOrderInputs(listEl);
            callReorder(listEl);
            listEl.__advMediaDragging = false;
            listEl.__advMediaSuppressClickUntil = Date.now() + 250;
        },
    });
};

const removeItems = (listEl, ids, deleteUrlTemplate, confirmModal) => {
    const requests = ids.map((id) => {
        const url = deleteUrlTemplate.replace('__MEDIA_ID__', id);
        return fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        }).then(() => id).catch(() => id);
    });

    Promise.all(requests).then((removedIds) => {
        removedIds.forEach((id) => {
            const holder = listEl.querySelector(`.adv-media-files-item-holder[data-file-id="${id}"]`);
            if (holder) {
                const item = holder.closest('.adv-media-files-item');
                if (item) {
                    item.remove();
                }
            }
        });
        updateOrderInputs(listEl);
        callReorder(listEl);
        closeModal(confirmModal);
        if (window.toastr && removedIds.length) {
            window.toastr.success(window.voyager ? window.voyager.__('voyager.generic.successfully_deleted') || 'Deleted' : 'Deleted');
        }
    });
};

const bindList = (listEl) => {
    bindSortable(listEl);
    updateOrderInputs(listEl);

    // State flags to avoid click vs drag conflicts
    listEl.__advMediaDragging = false;
    listEl.__advMediaSuppressClickUntil = 0;

    const fieldName = listEl.getAttribute('data-field-name');
    const deleteModal = document.getElementById(`adv-media-delete-modal-${fieldName}`);
    const propsModal = document.getElementById(`adv-media-props-modal-${fieldName}`);
    const inputId = listEl.getAttribute('data-input-id');
    const masterFileInput = inputId ? document.getElementById(inputId) : null;
    let pendingDeleteIds = [];
    let activeItem = null;
    const replaceInputs = new Map();
    const loadedProps = new Map();

    if (deleteModal) {
        const confirmBtn = deleteModal.querySelector('.adv-media-delete-confirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                const template = listEl.getAttribute('data-delete-url-template');
                if (pendingDeleteIds.length === 0 || !template) {
                    closeModal(deleteModal);
                    return;
                }
                removeItems(listEl, pendingDeleteIds, template, deleteModal);
                pendingDeleteIds = [];
            });
        }
    }

    const markButtons = document.querySelectorAll(`[data-files-gallery-list="${fieldName}"]`);

    const toggleBulkButtons = () => {
        const selected = listEl.querySelectorAll('.adv-media-files-item-holder.remove').length > 0;
        markButtons.forEach((btn) => {
            if (btn.classList.contains('bunch-adv-remove-holder')) {
                btn.classList.toggle('hidden', !selected);
            }
            if (btn.classList.contains('bunch-adv-media-files-unmark')) {
                btn.classList.toggle('hidden', !selected);
            }
            if (btn.classList.contains('bunch-adv-media-files-select-all')) {
                btn.classList.toggle('hidden', selected);
            }
        });
    };

    const toggleSelected = (holder) => {
        if (!holder) {
            return;
        }
        if (holder.classList.contains('remove')) {
            holder.classList.remove('remove');
        } else {
            holder.classList.add('remove');
        }
        toggleBulkButtons();
    };

    let pointerDown = null;
    listEl.addEventListener('pointerdown', (event) => {
        const holder = event.target.closest('.adv-media-files-item-holder');
        if (!holder) {
            pointerDown = null;
            return;
        }
        if (event.target.closest('.adv-media-files-actions')) {
            pointerDown = null;
            return;
        }
        pointerDown = {
            x: event.clientX,
            y: event.clientY,
            time: Date.now(),
            holder,
        };
    });

    listEl.addEventListener('pointerup', (event) => {
        if (!pointerDown) {
            return;
        }
        const now = Date.now();
        const suppressUntil = listEl.__advMediaSuppressClickUntil || 0;
        if (now < suppressUntil || listEl.__advMediaDragging) {
            pointerDown = null;
            return;
        }
        const dx = Math.abs((event.clientX || 0) - pointerDown.x);
        const dy = Math.abs((event.clientY || 0) - pointerDown.y);
        const dt = now - pointerDown.time;

        // Treat as click if there was no drag movement
        if (dx <= 5 && dy <= 5 && dt <= 600) {
            toggleSelected(pointerDown.holder);
        }
        pointerDown = null;
    });

    listEl.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.adv-media-files-remove');
        if (removeBtn) {
            const holder = removeBtn.closest('.adv-media-files-item-holder');
            const mediaId = holder ? holder.getAttribute('data-file-id') : null;
            if (mediaId && deleteModal) {
                pendingDeleteIds = [mediaId];
                openModal(deleteModal);
            }
            return;
        }

        const changeBtn = event.target.closest('.adv-media-files-change');
        if (changeBtn) {
            const holder = changeBtn.closest('.adv-media-files-item-holder');
            const mediaId = holder ? holder.getAttribute('data-file-id') : null;
            if (!mediaId || !holder) {
                return;
            }
            let fileInput = replaceInputs.get(mediaId);
            if (!fileInput) {
                fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.name = `${fieldName}_replace[${mediaId}]`;
                fileInput.accept = masterFileInput ? masterFileInput.getAttribute('accept') || 'image/*' : 'image/*';
                fileInput.className = 'adv-media-file-input';
                fileInput.dataset.fileId = mediaId;
                fileInput.style.position = 'absolute';
                fileInput.style.width = '1px';
                fileInput.style.height = '1px';
                fileInput.style.opacity = '0';
                fileInput.style.pointerEvents = 'none';
                const form = holder.closest('form');
                (form || document.body).appendChild(fileInput);
                replaceInputs.set(mediaId, fileInput);
            }
            fileInput.click();
            return;
        }

        const editBtn = event.target.closest('.adv-media-files-edit');
        if (editBtn && propsModal) {
            activeItem = editBtn.closest('.adv-media-files-item-holder');
            if (!activeItem) {
                return;
            }
            const mediaId = activeItem.getAttribute('data-file-id');
            if (!mediaId) return;

            const mediaUrl = buildMediaUrl(listEl, mediaId);

            const editorsPromise = (window.Voyager && typeof window.Voyager.loadEditors === 'function')
                ? window.Voyager.loadEditors().catch(() => {})
                : Promise.resolve();

            fetch(mediaUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data || data.status !== 'success' || !data.media) {
                        throw new Error(data && data.message ? data.message : 'Failed to load media props');
                    }

                    const props = (data.media.props && typeof data.media.props === 'object') ? data.media.props : {};
                    loadedProps.set(mediaId, props);

                    const titleInput = propsModal.querySelector('.modal-prop-title');
                    const altInput = propsModal.querySelector('.modal-prop-alt');
                    const extraInputs = propsModal.querySelectorAll('.modal-prop-extra');
                    const aceEditors = propsModal.querySelectorAll('.modal-prop-ace');

                    if (titleInput) titleInput.value = props.title || '';
                    if (altInput) altInput.value = props.alt || '';

                    extraInputs.forEach((input) => {
                        const key = input.getAttribute('data-extra-key');
                        input.value = key ? (props[key] || '') : '';
                    });

                    return Promise.resolve(editorsPromise).then(() => {
                        if (window.Voyager && window.Voyager.editors && typeof window.Voyager.editors.initAceEditors === 'function') {
                            window.Voyager.editors.initAceEditors(propsModal);
                        }

                        aceEditors.forEach((el) => {
                            const key = el.getAttribute('data-extra-key');
                            const editorId = el.id;
                            if (!key || !editorId) return;
                            const textarea = document.getElementById(editorId + '_textarea');
                            const value = props[key] || '';
                            if (textarea) textarea.value = value;
                            if (window.ace && typeof window.ace.edit === 'function') {
                                const editor = window.ace.edit(editorId);
                                editor.setValue(value, -1);
                                editor.clearSelection();
                                setTimeout(() => editor.resize(), 0);
                            }
                        });

                        openModal(propsModal);
                    });
                })
                .catch((err) => {
                    if (window.toastr) window.toastr.error(err.message || 'Error loading media');
                });
            return;
        }
    });

    if (propsModal) {
        const saveBtn = propsModal.querySelector('.modal-prop-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                if (!activeItem) {
                    closeModal(propsModal);
                    return;
                }
                const titleInput = propsModal.querySelector('.modal-prop-title');
                const altInput = propsModal.querySelector('.modal-prop-alt');
                const extraInputs = propsModal.querySelectorAll('.modal-prop-extra');
                const aceEditors = propsModal.querySelectorAll('.modal-prop-ace');
                const mediaId = activeItem.getAttribute('data-file-id');
                if (!mediaId) {
                    closeModal(propsModal);
                    return;
                }

                const mediaUrl = buildMediaUrl(listEl, mediaId);
                const updateUrl = buildUpdatePropsUrl(mediaUrl);

                const props = Object.assign({}, loadedProps.get(mediaId) || {});
                if (titleInput) props.title = titleInput.value;
                if (altInput) props.alt = altInput.value;

                extraInputs.forEach((input) => {
                    const key = input.getAttribute('data-extra-key');
                    if (key) props[key] = input.value;
                });

                aceEditors.forEach((el) => {
                    const key = el.getAttribute('data-extra-key');
                    const editorId = el.id;
                    if (!key || !editorId) return;
                    if (window.ace && typeof window.ace.edit === 'function') {
                        props[key] = window.ace.edit(editorId).getValue();
                    } else {
                        const textarea = document.getElementById(editorId + '_textarea');
                        props[key] = textarea ? textarea.value : '';
                    }
                });

                fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ props }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (!data || data.status !== 'success') {
                            throw new Error(data && data.message ? data.message : 'Error saving');
                        }
                        loadedProps.set(mediaId, props);
                        const item = activeItem.closest('.adv-media-files-item');
                        const display = item ? item.querySelector('.adv-media-prop-display') : null;
                        if (display) {
                            display.textContent = props.title || (window.voyager && window.voyager.__ ? window.voyager.__('voyager.generic.none') : '') || '...';
                        }
                        if (window.toastr) window.toastr.success((window.voyager && window.voyager.__ ? window.voyager.__('voyager.generic.successfully_updated') : '') || 'Saved');
                        closeModal(propsModal);
                    })
                    .catch((err) => {
                        if (window.toastr) window.toastr.error(err.message || 'Error saving');
                    });
            });
        }
    }

    listEl.addEventListener('change', (event) => {
        const fileInput = event.target.closest('.adv-media-file-input');
        if (fileInput) {
            const mediaId = fileInput.getAttribute('data-file-id');
            const holder = mediaId ? listEl.querySelector(`.adv-media-files-item-holder[data-file-id="${mediaId}"]`) : null;
            if (holder) {
                holder.classList.add('adv-media-file-replaced');
            }
            const item = holder ? holder.closest('.adv-media-files-item') : null;
            const label = item ? item.querySelector('.adv-media-files-filename') : null;
            if (label && fileInput.files && fileInput.files[0]) {
                label.innerHTML = `${fileInput.files[0].name} <i>${(fileInput.files[0].size / 1024).toFixed(1)} KB</i>`;
            }
        }
    });

    const bulkSelectBtn = document.querySelector(`.bunch-adv-media-files-select-all[data-files-gallery-list="${fieldName}"]`);
    const bulkUnmarkBtn = document.querySelector(`.bunch-adv-media-files-unmark[data-files-gallery-list="${fieldName}"]`);
    const bulkDeleteBtn = document.querySelector(`#bunch-adv-remove-${fieldName} .bunch-adv-media-files-remove`);

    if (bulkSelectBtn) {
        bulkSelectBtn.addEventListener('click', () => {
        listEl.querySelectorAll('.adv-media-files-item-holder').forEach((item) => item.classList.add('remove'));
            toggleBulkButtons();
        });
    }

    if (bulkUnmarkBtn) {
        bulkUnmarkBtn.addEventListener('click', () => {
            listEl.querySelectorAll('.adv-media-files-item-holder').forEach((item) => item.classList.remove('remove'));
            toggleBulkButtons();
        });
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', () => {
            const marked = Array.from(listEl.querySelectorAll('.adv-media-files-item-holder.remove'));
            const ids = marked
                .map((item) => item.getAttribute('data-file-id'))
                .filter(Boolean);
            if (ids.length && deleteModal) {
                pendingDeleteIds = ids;
                openModal(deleteModal);
            }
        });
    }

    // Props and order are saved immediately via API (like in the original VE field).
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.adv-media-files-list').forEach((listEl) => {
        bindList(listEl);
    });
});
