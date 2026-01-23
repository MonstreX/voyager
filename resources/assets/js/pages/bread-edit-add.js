import { showModal, hideModal } from '../core/bootstrap-compat';
import { getCsrfToken } from '../modules/csrf';
import { getToastr } from '../core/toastr';
import { initMultilingual } from '../multilingual';

let listenersAttached = false;
let legacyCropAttached = false;

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

const assignDefaults = (config) => {
    const init = window.Voyager && window.Voyager.init;
    if (init && typeof init.toggles === 'function') init.toggles();
    if (init && typeof init.datepickers === 'function') init.datepickers();
    if (config.isModelTranslatable) {
        initMultilingual(document.querySelectorAll('.side-body'), { editing: true });
    }
    if (init && typeof init.slugify === 'function') {
        const selector = config.slugifySelector || '.side-body input[data-slug-origin]';
        init.slugify(document.querySelectorAll(selector));
    }
    if (init && typeof init.tooltips === 'function') init.tooltips(document.querySelectorAll('[data-toggle="tooltip"]'));
};

const initLegacyImageCrop = () => {
    if (legacyCropAttached) {
        return;
    }
    if (!currentConfig || !currentConfig.mediaCropUrl) {
        return;
    }
    legacyCropAttached = true;

    const cropState = {
        modal: null,
        cropper: null,
        cropData: null,
        imageEl: null,
        widthEl: null,
        heightEl: null,
        aspectEl: null,
        maxWidthEl: null,
        maxHeightEl: null,
        prefix: null,
    };

    const destroyCropper = () => {
        if (cropState.cropper && typeof cropState.cropper.destroy === 'function') {
            cropState.cropper.destroy();
        }
        cropState.cropper = null;
        cropState.cropData = null;
    };

    const setAspect = () => {
        if (!cropState.cropper || !cropState.aspectEl) return;
        const raw = cropState.aspectEl.value;
        if (raw === 'free') {
            cropState.cropper.setAspectRatio(NaN);
            return;
        }
        const ratio = parseFloat(raw);
        cropState.cropper.setAspectRatio(Number.isFinite(ratio) ? ratio : NaN);
    };

    const openCropModal = (modal, imageEl, prefix) => {
        if (!modal || !imageEl) {
            return;
        }

        if (cropState.modal && cropState.modal !== modal) {
            destroyCropper();
        }

        cropState.modal = modal;
        cropState.imageEl = imageEl;
        cropState.prefix = prefix;
        cropState.widthEl = modal.querySelector(`.${prefix}-crop-width`);
        cropState.heightEl = modal.querySelector(`.${prefix}-crop-height`);
        cropState.aspectEl = modal.querySelector(`.${prefix}-crop-aspect`);
        cropState.maxWidthEl = modal.querySelector(`.${prefix}-crop-max-width`);
        cropState.maxHeightEl = modal.querySelector(`.${prefix}-crop-max-height`);

        const modalImg = modal.querySelector('.crop-container img');
        if (!modalImg) {
            return;
        }

        const src = imageEl.getAttribute('src') || '';
        modalImg.src = src ? (src.indexOf('?') >= 0 ? `${src}&t=${Date.now()}` : `${src}?t=${Date.now()}`) : '';

        modal.addEventListener('shown.bs.modal', function onShown() {
            modal.removeEventListener('shown.bs.modal', onShown);
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
                crop: function (e) {
                    const w = Math.round(e.detail.width);
                    const h = Math.round(e.detail.height);
                    if (cropState.widthEl) cropState.widthEl.textContent = `${w}px`;
                    if (cropState.heightEl) cropState.heightEl.textContent = `${h}px`;
                    cropState.cropData = {
                        x: Math.round(e.detail.x),
                        y: Math.round(e.detail.y),
                        width: w,
                        height: h,
                    };
                },
            });
            setAspect();
        });

        modal.addEventListener('hidden.bs.modal', function onHidden() {
            modal.removeEventListener('hidden.bs.modal', onHidden);
            destroyCropper();
            cropState.modal = null;
            cropState.imageEl = null;
            cropState.prefix = null;
        });

        if (cropState.aspectEl) {
            cropState.aspectEl.onchange = setAspect;
        }

        showModal(modal);
    };

    document.addEventListener('click', (event) => {
        const cropButton = event.target.closest('.bread-image-crop, .bread-multi-image-crop');
        if (!cropButton) {
            return;
        }

        const wrapper = cropButton.closest('[data-field-name]');
        const imageEl = wrapper ? wrapper.querySelector('img[data-file-name]') : null;
        const field = wrapper ? wrapper.getAttribute('data-field-name') : null;
        if (!imageEl || !field) {
            return;
        }

        const isMulti = cropButton.classList.contains('bread-multi-image-crop');
        const modalId = isMulti ? `bread-multi-image-crop-modal-${field}` : `bread-image-crop-modal-${field}`;
        const prefix = isMulti ? 'bread-multi-image' : 'bread-image';
        const modal = document.getElementById(modalId);

        openCropModal(modal, imageEl, prefix);
    });

    document.addEventListener('click', (event) => {
        const confirmBtn = event.target.closest('.bread-image-crop-confirm, .bread-multi-image-crop-confirm');
        if (!confirmBtn || !cropState.modal || !cropState.imageEl) {
            return;
        }
        if (!cropState.cropData) {
            hideModal(cropState.modal);
            return;
        }

        const rawPath = cropState.imageEl.getAttribute('data-file-name') || '';
        const normalized = rawPath.replace(/\\/g, '/');
        if (!normalized) {
            return;
        }

        const lastSlash = normalized.lastIndexOf('/');
        const uploadPath = lastSlash >= 0 ? normalized.slice(0, lastSlash) : '';
        const originImageName = lastSlash >= 0 ? normalized.slice(lastSlash + 1) : normalized;

        const payload = {
            upload_path: uploadPath,
            originImageName,
            x: cropState.cropData.x,
            y: cropState.cropData.y,
            width: cropState.cropData.width,
            height: cropState.cropData.height,
            createMode: 'false',
        };

        if (cropState.maxWidthEl && cropState.maxWidthEl.value) {
            const v = parseInt(cropState.maxWidthEl.value, 10);
            if (Number.isFinite(v) && v > 0) payload.max_width = v;
        }
        if (cropState.maxHeightEl && cropState.maxHeightEl.value) {
            const v = parseInt(cropState.maxHeightEl.value, 10);
            if (Number.isFinite(v) && v > 0) payload.max_height = v;
        }

        confirmBtn.disabled = true;

        fetch(currentConfig.mediaCropUrl, {
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
                if (!data || data.success !== true) {
                    throw new Error(data && data.message ? data.message : 'Crop failed');
                }

                const img = cropState.imageEl;
                const current = img.getAttribute('src') || '';
                const base = current.split('#')[0];
                img.setAttribute('src', `${base}#t=${Date.now()}`);

                const toastr = getToastr();
                toastr && toastr.success(data.message || 'Cropped');
                hideModal(cropState.modal);
            })
            .catch((err) => {
                const toastr = getToastr();
                toastr && toastr.error(err.message || 'Crop failed');
            })
            .finally(() => {
                confirmBtn.disabled = false;
            });
    });
};

const initMultiImagesSortable = () => {
    const SortableLib = window.Voyager && window.Voyager.Sortable;
    if (!SortableLib || !currentConfig || !currentConfig.mediaReorderUrl) {
        return;
    }

    document.querySelectorAll('.multi-images-list').forEach((listEl) => {
        if (listEl.__multiImagesSortable) {
            return;
        }

        const modelId = listEl.getAttribute('data-model-id');
        const field = listEl.getAttribute('data-field-name');
        if (!modelId || !field) {
            return;
        }

        const sortable = SortableLib.create(listEl, {
            animation: 150,
            handle: 'img',
            onEnd: () => {
                const order = [];
                listEl.querySelectorAll('img[data-file-name]').forEach((img) => {
                    const name = img.getAttribute('data-file-name');
                    if (name) {
                        order.push(name);
                    }
                });

                if (!order.length) {
                    return;
                }

                fetch(currentConfig.mediaReorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        slug: currentConfig.slug,
                        id: modelId,
                        field,
                        order,
                    }),
                })
                    .then((response) => response.json())
                    .then((response) => {
                        const toastr = getToastr();
                        if (response && response.data && response.data.status === 200) {
                            toastr && toastr.success(response.data.message);
                        } else {
                            toastr && toastr.error('Error updating order.');
                        }
                    })
                    .catch(() => {
                        const toastr = getToastr();
                        toastr && toastr.error('Error updating order.');
                    });
            },
        });

        listEl.__multiImagesSortable = sortable;
    });
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
    initLegacyImageCrop();
    initMultiImagesSortable();

    if (listenersAttached) return;
    listenersAttached = true;
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initBreadEditAdd());
};
