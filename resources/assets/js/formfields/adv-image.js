document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        if (event.target.closest('.single-adv-image-remove')) {
            const button = event.target.closest('.single-adv-image-remove');
            const mediaId = button.getAttribute('data-media-id');
            const field = button.getAttribute('data-field');
            const mediaDiv = document.getElementById('adv-image-' + field);

            if (confirm('Are you sure you want to delete this image?')) {
                fetch(`/admin/api/media/${mediaId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (mediaDiv) {
                            mediaDiv.remove();
                        }
                        // Reset file input
                        const fileInput = document.getElementById('adv-image-input-' + field);
                        if (fileInput) {
                            fileInput.value = '';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    });
});
