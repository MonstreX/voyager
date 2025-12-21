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

document.addEventListener('DOMContentLoaded', function() {
    if (listenersAttached) return;
    listenersAttached = true;

    let pendingDeleteData = null;
    let cropState = {
        mediaId: null,
        field: null,
        modal: null,
        cropper: null,
        cropData: null,
        shownHandler: null,
        hiddenHandler: null,
    };

    const getCsrfToken = () => {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        return tokenMeta ? tokenMeta.getAttribute('content') : '';
    };

    const buildDeleteUrl = (button, mediaId) => {
        const template = button.getAttribute('data-delete-url-template');
        if (template) {
            return template.replace('__MEDIA_ID__', mediaId);
        }
        const prefix = (window.voyagerPrefix || '/admin').replace(/\/?$/, '');
        return `${prefix}/api/media/${mediaId}`;
    };

    const buildCropUrl = (mediaId) => {
        const prefix = (window.voyagerPrefix || '/admin').replace(/\/?$/, '');
        return `${prefix}/api/media/${mediaId}/crop`;
    };

    const closeModal = (modal) => {
        if (!modal) return;
        hideModal(modal);
    };

    const openModal = (modal) => {
        if (!modal) return;
        showModal(modal);
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

    document.addEventListener('click', function(event) {
        const removeButton = event.target.closest('.single-adv-image-remove');
        if (!removeButton) {
            return;
        }

        const mediaId = removeButton.getAttribute('data-media-id');
        const field = removeButton.getAttribute('data-field');
        const mediaDiv = document.getElementById('adv-image-' + field);
        const modal = document.getElementById('adv-image-delete-modal-' + field);

        pendingDeleteData = { mediaId, field, mediaDiv, modal, button: removeButton };
        openModal(modal);
    });

    document.addEventListener('click', function(event) {
        const cropButton = event.target.closest('.single-adv-image-crop');
        if (!cropButton) {
            return;
        }

        const mediaId = cropButton.getAttribute('data-media-id');
        const field = cropButton.getAttribute('data-field');
        const mediaDiv = document.getElementById('adv-image-' + field);
        const previewImg = mediaDiv ? mediaDiv.querySelector('img') : null;

        const modal = document.getElementById('adv-image-crop-modal-' + field);
        const modalImg = modal ? modal.querySelector('.crop-container img') : null;
        const widthEl = modal ? modal.querySelector('.adv-image-crop-width') : null;
        const heightEl = modal ? modal.querySelector('.adv-image-crop-height') : null;
        const aspectSelect = modal ? modal.querySelector('.adv-image-crop-aspect') : null;

        if (!mediaId || !modal || !modalImg || !previewImg) {
            return;
        }

        // Cleanup old listeners/cropper if this modal was used before
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

    document.addEventListener('click', function(event) {
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

        fetch(buildCropUrl(cropState.mediaId), {
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
                toastr && toastr.success(getApiErrorMessage(data, 'Cropped'));
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

    document.addEventListener('click', function(event) {
        const confirmButton = event.target.closest('.adv-image-delete-confirm');
        if (!confirmButton || !pendingDeleteData) {
            return;
        }

        const { mediaId, field, mediaDiv, modal, button } = pendingDeleteData;
        const url = buildDeleteUrl(button, mediaId);

        const clearInput = document.querySelector(`input[name="${field}_clear"]`);
        if (clearInput) {
            clearInput.value = '1';
        }

        confirmButton.disabled = true;

        fetch(url, {
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
                toastr && typeof toastr.success === 'function' && toastr.success('Image deleted successfully');
            } else {
                const toastr = getToastr();
                toastr && typeof toastr.error === 'function' && toastr.error(getApiErrorMessage(data, 'Error deleting image'));
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
