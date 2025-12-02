/*--------------------
|
| TinyMCE default config
|
--------------------*/

const getMetaContent = (name) => {
    if (typeof document === 'undefined') {
        return '';
    }
    const meta = document.querySelector(`meta[name="${name}"]`);
    return meta ? meta.getAttribute('content') || '' : '';
};

const getInputValue = (id) => {
    if (typeof document === 'undefined') {
        return '';
    }
    const el = document.getElementById(id);
    return el ? el.value : '';
};

const setLoaderVisibility = (visible) => {
    if (typeof document === 'undefined') {
        return;
    }

    const loader = document.getElementById('voyager-loader');
    if (!loader) {
        return;
    }

    loader.style.zIndex = visible ? '10000' : '99';
    loader.style.display = visible ? 'block' : 'none';
};

const uploadTinyMceImage = async (file) => {
    const uploadUrl = getInputValue('upload_url');

    if (!uploadUrl) {
        throw new Error('TinyMCE upload_url field is missing');
    }

    const formdata = new FormData();
    formdata.append('image', file);

    const typeSlug = getInputValue('upload_type_slug');
    if (typeSlug) {
        formdata.append('type_slug', typeSlug);
    }

    setLoaderVisibility(true);

    try {
        const response = await fetch(uploadUrl, {
            method: 'POST',
            body: formdata,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getMetaContent('csrf-token') || '',
            },
        });

        if (!response.ok) {
            throw new Error(`TinyMCE image upload failed (${response.status})`);
        }

        return await response.text();
    } finally {
        setLoaderVisibility(false);
    }
};

const getDefaultBaseUrl = () => {
    if (typeof window !== 'undefined' && window.voyagerTinyMCEBase) {
        return window.voyagerTinyMCEBase;
    }

    const assetsPath = getMetaContent('assets-path') || '';
    return assetsPath.replace(/\/?$/, '/js');
};

const getConfig = function(options = {}) {

    const baseTinymceConfig = {
        menubar: false,
        selector: 'textarea.richTextBox',
        base_url: getDefaultBaseUrl(),
        skin: 'oxide',
        min_height: 600,
        resize: true,
        plugins: 'link image code table lists',
        extended_valid_elements : 'input[id|name|value|type|class|style|required|placeholder|autocomplete|onclick]',
        relative_urls: false,
        remove_script_host: true,
        convert_urls: true,
        file_picker_types: 'image',
        file_picker_callback: (callback, value, meta) => {
            if (meta.filetype == 'image') {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');

                input.addEventListener('change', async function handleImageUpload() {
                    if (!this.files || !this.files[0]) {
                        return;
                    }

                    try {
                        const url = await uploadTinyMceImage(this.files[0]);
                        callback(url);
                    } catch (error) {
                        console.error('TinyMCE image upload failed', error);
                        if (typeof window !== 'undefined' && window.toastr && typeof window.toastr.error === 'function') {
                            window.toastr.error('Image upload failed.');
                        }
                    }
                }, { once: true });

                input.click();
            }
        },
        toolbar: 'styles | bold italic underline | forecolor backcolor | alignleft aligncenter alignright | bullist numlist outdent indent | link image table | code',
        image_caption: true,
        image_title: true,
        init_instance_callback: function (editor) {
            if (typeof tinymce_init_callback !== "undefined") {
                tinymce_init_callback(editor);
            }
        },
        setup: function (editor) {
            if (typeof tinymce_setup_callback !== "undefined") {
                tinymce_setup_callback(editor);
            }
        }
    };

    return Object.assign({}, baseTinymceConfig, options);
}

export default { getConfig };
