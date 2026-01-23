import { showModal, hideModal } from '../core/bootstrap-compat';
import { getToastr } from '../core/toastr';

let listenersAttached = false;

const getApiErrorMessage = (payload, fallback) => {
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

const buildMediaUrl = (holder, mediaId) => {
    if (!holder) return '';
    const template = holder.getAttribute('data-delete-url-template');
    if (template) {
        return template.replace('__MEDIA_ID__', mediaId);
    }
    const prefix = (window.voyagerPrefix || '/admin').replace(/\/?$/, '');
    return `${prefix}/api/media/${mediaId}`;
};

const buildUpdatePropsUrl = (mediaUrl) => `${mediaUrl.replace(/\/$/, '')}/props`;
const buildCropUrl = (mediaUrl) => `${mediaUrl.replace(/\/$/, '')}/crop`;

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

const updateTitleDisplay = (holder, title) => {
    const item = holder ? holder.closest('.adv-media-files-item') : null;
    const display = item ? item.querySelector('.adv-media-prop-display') : null;
    if (!display) return;
    if (title) {
        display.textContent = title;
        return;
    }
    display.innerHTML = '<i>...</i>';
};

document.addEventListener('DOMContentLoaded', () => {
    if (listenersAttached) return;
    listenersAttached = true;

    let pendingDeleteData = null;
    let cropState = {
        mediaId: null,
        field: null,
        modal: null,
        holder: null,
        cropper: null,
        cropData: null,
        shownHandler: null,
        hiddenHandler: null,
    };

    const destroyCropper = () => {
        if (cropState.cropper && typeof cropState.cropper.destroy === 'function') {
            cropState.cropper.destroy();
        }
        cropState.cropper = null;
        cropState.cropData = null;
    };

    const setCropAspect = (aspectSelect) => {
        if (!cropState.cropper || !aspectSelect) {
            return;
        }
        const raw = aspectSelect.value;
        if (raw === 'free') {
            cropState.cropper.setAspectRatio(NaN);
            return;
        }
        const ratio = parseFloat(raw);
        cropState.cropper.setAspectRatio(Number.isFinite(ratio) ? ratio : NaN);
    };

    document.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.single-adv-image-remove');
        if (!removeButton) {
            return;
        }

        const holder = removeButton.closest('.adv-media-files-item-holder');
        if (!holder) {
            return;
        }
        const mediaId = holder.getAttribute('data-file-id');
        const field = holder.getAttribute('data-field-name');
        const mediaDiv = holder.closest('.adv-media-files-item');
        const modal = document.getElementById('adv-image-delete-modal-' + field);

        pendingDeleteData = { mediaId, field, mediaDiv, modal, holder };
        openModal(modal);
    });

    document.addEventListener('click', (event) => {
        const cropButton = event.target.closest('.single-adv-image-crop');
        if (!cropButton) {
            return;
        }

        const holder = cropButton.closest('.adv-media-files-item-holder');
        if (!holder) {
            return;
        }
        const mediaId = holder.getAttribute('data-file-id');
        const field = holder.getAttribute('data-field-name');
        const previewImg = holder.querySelector('img');

        const modal = document.getElementById('adv-image-crop-modal-' + field);
        const modalImg = modal ? modal.querySelector('.crop-container img') : null;
        const widthEl = modal ? modal.querySelector('.adv-image-crop-width') : null;
        const heightEl = modal ? modal.querySelector('.adv-image-crop-height') : null;
        const aspectSelect = modal ? modal.querySelector('.adv-image-crop-aspect') : null;

        if (!mediaId || !modal || !modalImg || !previewImg) {
            return;
        }

        if (cropState.modal && cropState.shownHandler) {
            cropState.modal.removeEventListener('shown.bs.modal', cropState.shownHandler);
        }
        if (cropState.modal && cropState.hiddenHandler) {
            cropState.modal.removeEventListener('hidden.bs.modal', cropState.hiddenHandler);
        }
        destroyCropper();

        cropState.mediaId = mediaId;
        cropState.field = field;
        cropState.modal = modal;
        cropState.holder = holder;

        const src = previewImg.getAttribute('src') || '';
        modalImg.src = src ? (src.indexOf('?') >= 0 ? `${src}&t=${Date.now()}` : `${src}?t=${Date.now()}`) : '';

        cropState.shownHandler = function() {
            destroyCropper();
            const Cropper = window.Voyager && window.Voyager.Cropper;
            if (typeof Cropper === 'undefined') {
                const toastr = getToastr();
                toastr && toastr.error('Cropper is not available');
                return;
            }

            cropState.cropper = new Cropper(modalImg, {
                viewMode: 1,
                responsive: true,
                background: false,
                crop: function(e) {
                    const w = Math.round(e.detail.width);
                    const h = Math.round(e.detail.height);
                    if (widthEl) widthEl.textContent = `${w}px`;
                    if (heightEl) heightEl.textContent = `${h}px`;
                    cropState.cropData = {
                        x: Math.round(e.detail.x),
                        y: Math.round(e.detail.y),
                        width: w,
                        height: h,
                    };
                },
            });

            setCropAspect(aspectSelect);

            setTimeout(() => {
                if (cropState.cropper && typeof cropState.cropper.resize === 'function') {
                    cropState.cropper.resize();
                }
            }, 0);
        };

        cropState.hiddenHandler = function() {
            destroyCropper();
            cropState.mediaId = null;
            cropState.field = null;
            cropState.modal = null;
            cropState.holder = null;
            cropState.shownHandler = null;
            cropState.hiddenHandler = null;
        };

        modal.addEventListener('shown.bs.modal', cropState.shownHandler);
        modal.addEventListener('hidden.bs.modal', cropState.hiddenHandler);

        if (aspectSelect) {
            aspectSelect.onchange = function() {
                setCropAspect(aspectSelect);
            };
        }

        openModal(modal);
    });

    document.addEventListener('click', (event) => {
        const confirmBtn = event.target.closest('.adv-image-crop-confirm');
        if (!confirmBtn || !cropState.mediaId || !cropState.modal) {
            return;
        }
        if (!cropState.cropData) {
            closeModal(cropState.modal);
            return;
        }

        const maxWidthEl = cropState.modal.querySelector('.adv-image-crop-max-width');
        const maxHeightEl = cropState.modal.querySelector('.adv-image-crop-max-height');

        const payload = {
            x: cropState.cropData.x,
            y: cropState.cropData.y,
            width: cropState.cropData.width,
            height: cropState.cropData.height,
        };

        if (maxWidthEl && maxWidthEl.value) {
            const v = parseInt(maxWidthEl.value, 10);
            if (Number.isFinite(v) && v > 0) payload.max_width = v;
        }
        if (maxHeightEl && maxHeightEl.value) {
            const v = parseInt(maxHeightEl.value, 10);
            if (Number.isFinite(v) && v > 0) payload.max_height = v;
        }

        confirmBtn.disabled = true;
        const mediaUrl = buildMediaUrl(cropState.holder, cropState.mediaId);

        fetch(buildCropUrl(mediaUrl), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then(response => response.json())
            .then(data => {
                if (!data || data.status !== 'success') {
                    throw new Error(getApiErrorMessage(data, 'Crop failed'));
                }

                const mediaDiv = document.getElementById('adv-image-' + cropState.field);
                const previewImg = mediaDiv ? mediaDiv.querySelector('img') : null;
                if (previewImg) {
                    const current = previewImg.getAttribute('src') || '';
                    const base = current.split('?')[0];
                    previewImg.setAttribute('src', `${base}?t=${Date.now()}`);
                }

                const toastr = getToastr();
                toastr && toastr.success(getApiErrorMessage(data, translate('voyager.media.success_crop_image', 'Cropped')));
                closeModal(cropState.modal);
            })
            .catch(err => {
                const toastr = getToastr();
                toastr && toastr.error(err.message || 'Crop failed');
            })
            .finally(() => {
                confirmBtn.disabled = false;
            });
    });

    document.addEventListener('click', (event) => {
        const editButton = event.target.closest('.adv-image-edit');
        if (!editButton) {
            return;
        }

        const holder = editButton.closest('.adv-media-files-item-holder');
        if (!holder) {
            return;
        }

        const mediaId = holder.getAttribute('data-file-id');
        const field = holder.getAttribute('data-field-name');
        const modal = document.getElementById('adv-image-props-modal-' + field);
        if (!modal || !mediaId) {
            return;
        }

        const mediaUrl = buildMediaUrl(holder, mediaId);

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
                    throw new Error(getApiErrorMessage(data, 'Failed to load media props'));
                }

                const props = (media.props && typeof media.props === 'object') ? media.props : {};
                const titleInput = modal.querySelector('.modal-prop-title');
                const altInput = modal.querySelector('.modal-prop-alt');
                if (titleInput) titleInput.value = props.title || '';
                if (altInput) altInput.value = props.alt || '';

                modal.__advImageHolder = holder;
                modal.__advImageMediaId = mediaId;

                openModal(modal);
            })
            .catch((err) => {
                const toastr = getToastr();
                toastr && toastr.error(err.message || 'Error loading media');
            });
    });

    document.addEventListener('click', (event) => {
        const saveButton = event.target.closest('.modal-adv-image-props .modal-prop-save');
        if (!saveButton) {
            return;
        }

        const modal = saveButton.closest('.modal-adv-image-props');
        const holder = modal ? modal.__advImageHolder : null;
        const mediaId = modal ? modal.__advImageMediaId : null;
        if (!modal || !holder || !mediaId) {
            closeModal(modal);
            return;
        }

        const titleInput = modal.querySelector('.modal-prop-title');
        const altInput = modal.querySelector('.modal-prop-alt');
        const titleValue = titleInput ? titleInput.value : '';
        const altValue = altInput ? altInput.value : '';

        const mediaUrl = buildMediaUrl(holder, mediaId);
        const updateUrl = buildUpdatePropsUrl(mediaUrl);

        saveButton.disabled = true;

        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                props: {
                    title: titleValue,
                    alt: altValue,
                },
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data || data.status !== 'success') {
                    throw new Error(getApiErrorMessage(data, 'Error saving'));
                }

                updateTitleDisplay(holder, titleValue);
                const img = holder.querySelector('img');
                if (img) img.setAttribute('alt', altValue || '');

                const field = holder.getAttribute('data-field-name');
                const titleHidden = document.querySelector(`input[name="${field}_title"]`);
                const altHidden = document.querySelector(`input[name="${field}_alt"]`);
                if (titleHidden) titleHidden.value = titleValue || '';
                if (altHidden) altHidden.value = altValue || '';

                const toastr = getToastr();
                toastr &&
                    toastr.success(getApiErrorMessage(data, translate('voyager.generic.successfully_updated', 'Saved')));
                closeModal(modal);
            })
            .catch((err) => {
                const toastr = getToastr();
                toastr && toastr.error(err.message || 'Error saving');
            })
            .finally(() => {
                saveButton.disabled = false;
            });
    });

    document.addEventListener('click', (event) => {
        const confirmButton = event.target.closest('.adv-image-delete-confirm');
        if (!confirmButton || !pendingDeleteData) {
            return;
        }

        const { mediaId, field, mediaDiv, modal, holder } = pendingDeleteData;
        const mediaUrl = buildMediaUrl(holder, mediaId);

        const clearInput = document.querySelector(`input[name="${field}_clear"]`);
        if (clearInput) {
            clearInput.value = '1';
        }
        const titleHidden = document.querySelector(`input[name="${field}_title"]`);
        const altHidden = document.querySelector(`input[name="${field}_alt"]`);
        if (titleHidden) titleHidden.value = '';
        if (altHidden) altHidden.value = '';

        confirmButton.disabled = true;

        fetch(mediaUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => {
                closeModal(modal);
                confirmButton.disabled = false;

                if (data.status === 'success') {
                    if (mediaDiv) {
                        mediaDiv.remove();
                    }
                    const fileInput = document.getElementById('adv-image-input-' + field);
                    if (fileInput) {
                        fileInput.value = '';
                    }
                    const toastr = getToastr();
                    toastr &&
                        typeof toastr.success === 'function' &&
                        toastr.success(getApiErrorMessage(data, translate('voyager.generic.successfully_deleted', 'Deleted')));
                } else {
                    const toastr = getToastr();
                    toastr &&
                        typeof toastr.error === 'function' &&
                        toastr.error(getApiErrorMessage(data, translate('voyager.generic.error_deleting', 'Error deleting')));
                }
                pendingDeleteData = null;
            })
            .catch(error => {
                closeModal(modal);
                confirmButton.disabled = false;
                const toastr = getToastr();
                toastr && typeof toastr.error === 'function' && toastr.error('Error deleting image');
                console.error('Error:', error);
                pendingDeleteData = null;
            });
    });
});
