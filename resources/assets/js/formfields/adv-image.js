document.addEventListener('DOMContentLoaded', () => {
    const imageDropzones = document.querySelectorAll('.adv-image-dropzone');

    imageDropzones.forEach(dropzone => {
        initializeDropzone(dropzone);
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('.adv-image-delete')) {
            handleImageDelete(e);
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('adv-image-title') || e.target.classList.contains('adv-image-alt')) {
            handlePropChange(e);
        }
    });
});

function initializeDropzone(dropzone) {
    const field = dropzone.dataset.field;
    const modelType = dropzone.dataset.modelType;
    const modelId = dropzone.dataset.modelId;
    const collection = dropzone.dataset.collection;

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('dragover');
    });

    dropzone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('dragover');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            uploadImage(files[0], field, modelType, modelId, collection);
        }
    });

    dropzone.addEventListener('click', () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                uploadImage(e.target.files[0], field, modelType, modelId, collection);
            }
        });
        input.click();
    });
}

function uploadImage(file, field, modelType, modelId, collection) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('model_type', modelType);
    formData.append('model_id', modelId);
    formData.append('collection_name', collection);

    const uploadUrl = document.head.querySelector('meta[name="voyager-upload-url"]')?.getAttribute('content')
        || '/admin/api/media/upload';

    fetch(uploadUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.media) {
            updateImagePreview(field, data.media);
            document.getElementById(`adv-image-field-${field}`).value = data.media.id;
            window.Voyager.toastr.success('Image uploaded successfully');
        } else {
            window.Voyager.toastr.error(data.message || 'Upload failed');
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        window.Voyager.toastr.error('Upload error: ' + error.message);
    });
}

function updateImagePreview(field, media) {
    const preview = document.getElementById(`adv-image-preview-${field}`);

    const html = `
        <div class="adv-image-current">
            <div class="adv-image-item">
                <img src="${media.path}" alt="${media.file_name}" class="img-thumbnail">
                <div class="adv-image-info">
                    <p class="filename">${media.file_name}</p>
                    <p class="filesize">${formatBytes(media.size)}</p>
                    <div class="adv-image-props">
                        <input type="text" class="form-control adv-image-title" placeholder="Title" value="" data-media-id="${media.id}" data-prop="title">
                        <input type="text" class="form-control adv-image-alt" placeholder="Alt Text" value="" data-media-id="${media.id}" data-prop="alt">
                    </div>
                    <button type="button" class="btn btn-sm btn-danger adv-image-delete" data-media-id="${media.id}" data-field="${field}">
                        <i class="voyager-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    `;

    preview.innerHTML = html;
}

function handleImageDelete(e) {
    const button = e.target.closest('.adv-image-delete');
    const mediaId = button.dataset.mediaId;
    const field = button.dataset.field;

    if (!confirm('Are you sure you want to delete this image?')) {
        return;
    }

    const deleteUrl = `/admin/api/media/${mediaId}`;

    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById(`adv-image-field-${field}`).value = '';
            document.getElementById(`adv-image-preview-${field}`).innerHTML =
                '<div class="adv-image-empty"><p class="text-muted">No image selected</p></div>';
            window.Voyager.toastr.success('Image deleted successfully');
        } else {
            window.Voyager.toastr.error(data.message || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        window.Voyager.toastr.error('Delete error: ' + error.message);
    });
}

function handlePropChange(e) {
    const input = e.target;
    const mediaId = input.dataset.mediaId;
    const propKey = input.dataset.prop;
    const propValue = input.value;

    const propsUrl = `/admin/api/media/${mediaId}/props`;

    fetch(propsUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            props: {
                [propKey]: propValue
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.Voyager.toastr.success('Property updated');
        } else {
            window.Voyager.toastr.error(data.message || 'Update failed');
        }
    })
    .catch(error => {
        console.error('Update error:', error);
    });
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}
