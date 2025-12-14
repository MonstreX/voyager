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
                if (window.VoyagerBootstrapCompat && typeof window.VoyagerBootstrapCompat.showModal === 'function') {
                    window.VoyagerBootstrapCompat.showModal(modal);
                } else if (window.Voyager && window.Voyager.bootstrap && typeof window.Voyager.bootstrap.showModal === 'function') {
                    window.Voyager.bootstrap.showModal(modal);
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
                        if (window.VoyagerBootstrapCompat && typeof window.VoyagerBootstrapCompat.hideModal === 'function') {
                            window.VoyagerBootstrapCompat.hideModal(modal);
                        } else if (window.Voyager && window.Voyager.bootstrap && typeof window.Voyager.bootstrap.hideModal === 'function') {
                            window.Voyager.bootstrap.hideModal(modal);
                        }
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
                    const modal = document.getElementById('adv-image-delete-modal');
                    if (modal) {
                        if (window.VoyagerBootstrapCompat && typeof window.VoyagerBootstrapCompat.hideModal === 'function') {
                            window.VoyagerBootstrapCompat.hideModal(modal);
                        } else if (window.Voyager && window.Voyager.bootstrap && typeof window.Voyager.bootstrap.hideModal === 'function') {
                            window.Voyager.bootstrap.hideModal(modal);
                        }
                    }
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
