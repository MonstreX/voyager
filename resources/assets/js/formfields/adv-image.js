document.addEventListener('DOMContentLoaded', function() {
    let pendingDeleteData = null;

    document.addEventListener('click', function(event) {
        if (event.target.closest('.single-adv-image-remove')) {
            const button = event.target.closest('.single-adv-image-remove');
            const mediaId = button.getAttribute('data-media-id');
            const field = button.getAttribute('data-field');
            const mediaDiv = document.getElementById('adv-image-' + field);

            pendingDeleteData = { mediaId, field, mediaDiv };

            const modal = document.getElementById('adv-image-delete-modal');
            if (modal) {
                const bootstrapCompat = window.VoyagerBootstrapCompat;
                if (bootstrapCompat && typeof bootstrapCompat.showModal === 'function') {
                    bootstrapCompat.showModal(modal);
                } else {
                    modal.classList.add('in');
                    modal.style.display = 'block';
                    modal.setAttribute('aria-hidden', 'false');
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade in';
                    backdrop.dataset.modalTarget = modal.id;
                    document.body.appendChild(backdrop);
                    document.body.classList.add('modal-open');
                }
            }
        }
    });

    const confirmButton = document.getElementById('adv-image-delete-confirm');
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            if (pendingDeleteData) {
                const { mediaId, field, mediaDiv } = pendingDeleteData;

                fetch(`/admin/api/media/${mediaId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const modal = document.getElementById('adv-image-delete-modal');
                    if (modal) {
                        modal.classList.remove('in');
                        modal.style.display = 'none';
                        modal.setAttribute('aria-hidden', 'true');
                        const backdrop = document.querySelector('[data-modal-target="adv-image-delete-modal"]');
                        if (backdrop) {
                            backdrop.remove();
                        }
                        document.body.classList.remove('modal-open');
                    }

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
                    if (window.toastr && typeof window.toastr.error === 'function') {
                        window.toastr.error('Error deleting image');
                    }
                    console.error('Error:', error);
                    pendingDeleteData = null;
                });
            }
        });
    }
});
