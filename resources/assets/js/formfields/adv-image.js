document.addEventListener('DOMContentLoaded', function() {
    let pendingDeleteData = null;

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

    const closeModal = (modal) => {
        if (!modal) {
            return;
        }
        if (window.VoyagerBootstrapCompat && typeof window.VoyagerBootstrapCompat.hideModal === 'function') {
            window.VoyagerBootstrapCompat.hideModal(modal);
        } else if (window.Voyager && window.Voyager.bootstrap && typeof window.Voyager.bootstrap.hideModal === 'function') {
            window.Voyager.bootstrap.hideModal(modal);
        }
    };

    const openModal = (modal) => {
        if (!modal) {
            return;
        }
        if (window.VoyagerBootstrapCompat && typeof window.VoyagerBootstrapCompat.showModal === 'function') {
            window.VoyagerBootstrapCompat.showModal(modal);
        } else if (window.Voyager && window.Voyager.bootstrap && typeof window.Voyager.bootstrap.showModal === 'function') {
            window.Voyager.bootstrap.showModal(modal);
        }
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
                if (window.toastr && typeof window.toastr.success === 'function') {
                    window.toastr.success('Image deleted successfully');
                }
            }
            pendingDeleteData = null;
        })
        .catch(error => {
            closeModal(modal);
            confirmButton.disabled = false;
            if (window.toastr && typeof window.toastr.error === 'function') {
                window.toastr.error('Error deleting image');
            }
            console.error('Error:', error);
            pendingDeleteData = null;
        });
    });
});
