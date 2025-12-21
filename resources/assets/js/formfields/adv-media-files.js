import { showModal, hideModal } from '../core/bootstrap-compat';
import { getToastr } from '../core/toastr';

const getApiMessage = (payload, fallback) => {
    if (payload && payload.error && typeof payload.error.message === 'string' && payload.error.message) {
        return payload.error.message;
    }
    if (payload && typeof payload.message === 'string' && payload.message) {
        return payload.message;
    }
    return fallback;
};

const translate = (key, fallback) => {
    if (window.voyager && typeof window.voyager.__ === 'function') {
        const translated = window.voyager.__(key);
        if (translated) {
            return translated;
        }
    }
    return fallback || key;
};

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
            if (!data || data.status !== 'success') {
                throw new Error(getApiMessage(data, 'Failed to update order'));
            }
            const toastr = getToastr();
            toastr && toastr.success(getApiMessage(data, translate('voyager.generic.successfully_updated', 'Updated')));
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
const buildCropUrl = (mediaUrl) => `${mediaUrl.replace(/\/$/, '')}/crop`;

const bindSortable = (listEl) => {
    const SortableLib = window.Voyager && window.Voyager.Sortable;
    if (!SortableLib || typeof SortableLib.create !== 'function') {
        return;
    }
    SortableLib.create(listEl, {
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
        })
            .then(async (response) => {
                let payload = null;
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = null;
                }

                if (payload && payload.status === 'success') {
                    return { id, ok: true, payload };
                }

                return { id, ok: false, payload };
            })
            .catch(() => ({ id, ok: false, payload: null }));
    });

    Promise.all(requests).then((results) => {
        const removedIds = results.filter((r) => r && r.ok).map((r) => r.id);
        const failed = results.filter((r) => r && !r.ok);
        const firstSuccessPayload = results.find((r) => r && r.ok && r.payload)?.payload || null;

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
        const toastr = getToastr();
        if (toastr && removedIds.length) {
            toastr.success(getApiMessage(firstSuccessPayload, translate('voyager.generic.successfully_deleted', 'Deleted')));
        }
        if (toastr && failed.length) {
            const firstMessage = getApiMessage(failed[0].payload, translate('voyager.generic.error_deleting', 'Error deleting'));
            toastr.error(firstMessage);
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
    let cropTarget = null;
    let cropper = null;
    let cropData = null;

    const fieldNameForModal = listEl.getAttribute('data-field-name');
    const cropModal = document.getElementById(`adv-media-crop-modal-${fieldNameForModal}`);
    const cropConfirm = cropModal ? cropModal.querySelector('.adv-media-crop-confirm') : null;
    const cropImg = cropModal ? cropModal.querySelector('img') : null;
    const cropWidthEl = cropModal ? cropModal.querySelector('.adv-media-crop-width') : null;
    const cropHeightEl = cropModal ? cropModal.querySelector('.adv-media-crop-height') : null;
    const cropAspectEl = cropModal ? cropModal.querySelector('.adv-media-crop-aspect') : null;
    const cropMaxWidthEl = cropModal ? cropModal.querySelector('.adv-media-crop-max-width') : null;
    const cropMaxHeightEl = cropModal ? cropModal.querySelector('.adv-media-crop-max-height') : null;

    if (cropModal) {
        cropModal.addEventListener('shown.bs.modal', function () {
            if (!cropImg || !cropTarget) {
                return;
            }
            if (cropper && typeof cropper.destroy === 'function') {
                cropper.destroy();
                cropper = null;
            }
            const Cropper = window.Voyager && window.Voyager.Cropper;
            if (typeof Cropper === 'undefined') {
                const toastr = getToastr();
                toastr && toastr.error('Cropper is not available');
                return;
            }
            cropper = new Cropper(cropImg, {
                viewMode: 1,
                responsive: true,
                background: false,
                crop: function (e) {
                    const w = Math.round(e.detail.width);
                    const h = Math.round(e.detail.height);
                    if (cropWidthEl) cropWidthEl.textContent = `${w}px`;
                    if (cropHeightEl) cropHeightEl.textContent = `${h}px`;
                    cropData = {
                        x: Math.round(e.detail.x),
                        y: Math.round(e.detail.y),
                        width: w,
                        height: h,
                    };
                },
            });

            if (cropAspectEl) {
                const raw = cropAspectEl.value;
                if (raw === 'free') {
                    cropper.setAspectRatio(NaN);
                } else {
                    const ratio = parseFloat(raw);
                    cropper.setAspectRatio(Number.isFinite(ratio) ? ratio : NaN);
                }
            }
        });

        cropModal.addEventListener('hidden.bs.modal', function () {
            if (cropper && typeof cropper.destroy === 'function') {
                cropper.destroy();
                cropper = null;
            }
            cropTarget = null;
            cropData = null;
            if (cropImg) cropImg.src = '';
            if (cropWidthEl) cropWidthEl.textContent = '0px';
            if (cropHeightEl) cropHeightEl.textContent = '0px';
            if (cropAspectEl) cropAspectEl.value = 'free';
            if (cropMaxWidthEl) cropMaxWidthEl.value = '';
            if (cropMaxHeightEl) cropMaxHeightEl.value = '';
        });
    }

    if (cropAspectEl) {
        cropAspectEl.addEventListener('change', () => {
            if (!cropper) {
                return;
            }
            const raw = cropAspectEl.value;
            if (raw === 'free') {
                cropper.setAspectRatio(NaN);
                return;
            }
            const ratio = parseFloat(raw);
            cropper.setAspectRatio(Number.isFinite(ratio) ? ratio : NaN);
        });
    }

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

        const cropBtn = event.target.closest('.adv-media-files-crop');
        if (cropBtn && cropModal) {
            const holder = cropBtn.closest('.adv-media-files-item-holder');
            if (!holder) return;
            const isImage = holder.getAttribute('data-is-image');
            if (isImage !== '1') {
                return;
            }
            const img = holder.querySelector('img');
            if (!img || !cropImg) {
                return;
            }
            cropTarget = holder;
            const src = img.getAttribute('src') || '';
            cropImg.src = src ? (src.indexOf('?') >= 0 ? `${src}&t=${Date.now()}` : `${src}?t=${Date.now()}`) : '';
            openModal(cropModal);
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
                    const media = data && data.data && data.data.media ? data.data.media : null;
                    if (!data || data.status !== 'success' || !media) {
                        throw new Error(getApiMessage(data, 'Failed to load media props'));
                    }

                    const props = (media.props && typeof media.props === 'object') ? media.props : {};
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
                    const toastr = getToastr();
                    toastr && toastr.error(err.message || 'Error loading media');
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
                            throw new Error(getApiMessage(data, 'Error saving'));
                        }
                        loadedProps.set(mediaId, props);
                        const item = activeItem.closest('.adv-media-files-item');
                        const display = item ? item.querySelector('.adv-media-prop-display') : null;
                        if (display) {
                            display.textContent = props.title || (window.voyager && window.voyager.__ ? window.voyager.__('voyager.generic.none') : '') || '...';
                        }
                        const toastr = getToastr();
                        toastr &&
                            toastr.success(getApiMessage(data, translate('voyager.generic.successfully_updated', 'Saved')));
                        closeModal(propsModal);
                    })
                    .catch((err) => {
                        const toastr = getToastr();
                        toastr && toastr.error(err.message || 'Error saving');
                    });
            });
        }
    }

    if (cropConfirm && cropModal) {
        cropConfirm.addEventListener('click', () => {
            if (!cropTarget) {
                closeModal(cropModal);
                return;
            }
            const mediaId = cropTarget.getAttribute('data-file-id');
            if (!mediaId || !cropData) {
                closeModal(cropModal);
                return;
            }

            const maxWidth = cropMaxWidthEl && cropMaxWidthEl.value ? parseInt(cropMaxWidthEl.value, 10) : null;
            const maxHeight = cropMaxHeightEl && cropMaxHeightEl.value ? parseInt(cropMaxHeightEl.value, 10) : null;

            const mediaUrl = buildMediaUrl(listEl, mediaId);
            const cropUrl = buildCropUrl(mediaUrl);

            const payload = {
                x: cropData.x,
                y: cropData.y,
                width: cropData.width,
                height: cropData.height,
            };
            if (maxWidth && maxWidth > 0) payload.max_width = maxWidth;
            if (maxHeight && maxHeight > 0) payload.max_height = maxHeight;

            cropConfirm.disabled = true;

            fetch(cropUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data || data.status !== 'success') {
                        throw new Error(getApiMessage(data, 'Crop failed'));
                    }
                    const img = cropTarget ? cropTarget.querySelector('img') : null;
                    if (img) {
                        const current = img.getAttribute('src') || '';
                        const base = current.split('?')[0];
                        img.setAttribute('src', `${base}?t=${Date.now()}`);
                    }
                    const toastr = getToastr();
                    toastr && toastr.success(getApiMessage(data, translate('voyager.media.success_crop_image', 'Cropped')));
                    closeModal(cropModal);
                })
                .catch((err) => {
                    const toastr = getToastr();
                    toastr && toastr.error(err.message || 'Crop failed');
                })
                .finally(() => {
                    cropConfirm.disabled = false;
                });
        });
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
