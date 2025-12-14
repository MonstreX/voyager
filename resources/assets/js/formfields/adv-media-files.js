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
        const orderLabel = item.closest('.adv-media-files-item')?.querySelector('.adv-media-files-order');
        if (orderLabel) {
            orderLabel.textContent = String(index + 1);
        }
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
    }).catch(() => {});
};

const bindSortable = (listEl) => {
    if (typeof Sortable === 'undefined') {
        return;
    }
    Sortable.create(listEl, {
        animation: 200,
        sort: true,
        scroll: true,
        onEnd: () => {
            updateOrderInputs(listEl);
            callReorder(listEl);
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
                holder.closest('.adv-media-files-item')?.remove();
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

    const fieldName = listEl.getAttribute('data-field-name');
    const deleteModal = document.getElementById(`adv-media-delete-modal-${fieldName}`);
    const deleteUrlTemplate = listEl.getAttribute('data-delete-url-template');
    const propsModal = document.getElementById(`adv-media-props-modal-${fieldName}`);
    const inputId = listEl.getAttribute('data-input-id');
    const masterFileInput = inputId ? document.getElementById(inputId) : null;
    let pendingDeleteIds = [];
    let activeItem = null;
    const replaceInputs = new Map();

    if (deleteModal) {
        const confirmBtn = deleteModal.querySelector('.adv-media-delete-confirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                if (pendingDeleteIds.length === 0 || !deleteUrlTemplate) {
                    closeModal(deleteModal);
                    return;
                }
                removeItems(listEl, pendingDeleteIds, deleteUrlTemplate, deleteModal);
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

        const markBtn = event.target.closest('.adv-media-files-mark');
        if (markBtn) {
            const holder = markBtn.closest('.adv-media-files-item-holder');
            holder?.classList.toggle('remove');
            toggleBulkButtons();
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
            const titleInput = propsModal.querySelector('.modal-prop-title');
            const altInput = propsModal.querySelector('.modal-prop-alt');
            const extraInputs = propsModal.querySelectorAll('.modal-prop-extra');
            const mediaId = activeItem.getAttribute('data-file-id');
            if (mediaId) {
                if (titleInput) {
                    titleInput.value = activeItem.getAttribute('data-title') || '';
                }
                if (altInput) {
                    altInput.value = activeItem.getAttribute('data-alt') || '';
                }
                extraInputs.forEach((input) => {
                    const key = input.getAttribute('data-extra-key');
                    input.value = activeItem.getAttribute(`data-extra-${key}`) || '';
                });
            }
            openModal(propsModal);
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
                const mediaId = activeItem.getAttribute('data-file-id');
                if (mediaId) {
                    if (titleInput) {
                        activeItem.setAttribute('data-title', titleInput.value);
                        const display = activeItem.closest('.adv-media-files-item')?.querySelector('.adv-media-prop-display');
                        if (display) {
                            display.textContent = titleInput.value || window.voyager?.__('voyager.generic.none') || '...';
                        }
                    }
                    if (altInput) {
                        activeItem.setAttribute('data-alt', altInput.value);
                    }
                    extraInputs.forEach((input) => {
                        const key = input.getAttribute('data-extra-key');
                        activeItem.setAttribute(`data-extra-${key}`, input.value);
                    });
                }
                closeModal(propsModal);
            });
        }
    }

    listEl.addEventListener('change', (event) => {
        const fileInput = event.target.closest('.adv-media-file-input');
        if (fileInput) {
            const mediaId = fileInput.getAttribute('data-file-id');
            const holder = mediaId ? listEl.querySelector(`.adv-media-files-item-holder[data-file-id="${mediaId}"]`) : null;
            holder?.classList.add('adv-media-file-replaced');
            const label = holder?.closest('.adv-media-files-item')?.querySelector('.adv-media-files-filename');
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

    const form = listEl.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            const order = collectOrder(listEl);
            order.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `${fieldName}_order[]`;
                input.value = id;
                form.appendChild(input);
            });

            listEl.querySelectorAll('.adv-media-files-item-holder').forEach((item) => {
                const mediaId = item.getAttribute('data-file-id');
                if (!mediaId) return;
                const title = item.getAttribute('data-title') || '';
                const alt = item.getAttribute('data-alt') || '';

                const titleInput = document.createElement('input');
                titleInput.type = 'hidden';
                titleInput.name = `${fieldName}_props[${mediaId}][title]`;
                titleInput.value = title;
                form.appendChild(titleInput);

                const altInput = document.createElement('input');
                altInput.type = 'hidden';
                altInput.name = `${fieldName}_props[${mediaId}][alt]`;
                altInput.value = alt;
                form.appendChild(altInput);

                item.getAttributeNames()
                    .filter((name) => name.startsWith('data-extra-'))
                    .forEach((attr) => {
                        const key = attr.replace('data-extra-', '');
                        const value = item.getAttribute(attr) || '';
                        const extraInput = document.createElement('input');
                        extraInput.type = 'hidden';
                        extraInput.name = `${fieldName}_props[${mediaId}][${key}]`;
                        extraInput.value = value;
                        form.appendChild(extraInput);
                    });
            });
        }, { once: false });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.adv-media-files-list').forEach((listEl) => {
        bindList(listEl);
    });
});
