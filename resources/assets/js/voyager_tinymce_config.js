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

const ensureAceModal = () => {
    if (document.getElementById('voyager-ace-code-modal')) {
        return;
    }
    const modalHtml = `
        <div class="modal fade" id="voyager-ace-code-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document" style="width: 90%;">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Source Code</h4>
                    </div>
                    <div class="modal-body">
                        <div id="voyager-ace-code-editor" style="height: 70vh; width: 100%;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="voyager-ace-code-save">Update</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
};

let aceEditor = null;
let currentTinyMce = null;

const openAceModal = (editor) => {
    ensureAceModal();
    currentTinyMce = editor;
    const modal = document.getElementById('voyager-ace-code-modal');
    
    if (!aceEditor) {
        // Config Ace
        const assetsMeta = document.querySelector('meta[name="assets-path"]');
        const assetsBase = assetsMeta ? assetsMeta.getAttribute('content') : '';
        const aceBaseMeta = document.querySelector('meta[name="voyager-ace-base"]');
        const explicitAceBase = window.voyagerAceBase || (aceBaseMeta ? aceBaseMeta.getAttribute('content') : '') || '';
        const fallbackBase = assetsBase.replace(/\/?$/, '/') + 'js/ace/libs';
        const aceBasePath = (explicitAceBase || fallbackBase).replace(/\/?$/, '/');
        
        if (window.ace) {
            window.ace.config.set("basePath", aceBasePath);
            window.ace.config.set("themePath", aceBasePath);
            window.ace.config.set("modePath", aceBasePath);
            
            aceEditor = window.ace.edit('voyager-ace-code-editor');
            aceEditor.setTheme('ace/theme/monokai');
            aceEditor.session.setMode('ace/mode/html');
            aceEditor.setShowPrintMargin(false);
        }
    }
    
    if (aceEditor) {
        aceEditor.setValue(editor.getContent({ source_view: true }), -1);
        
        const saveBtn = document.getElementById('voyager-ace-code-save');
        saveBtn.onclick = () => {
            currentTinyMce.setContent(aceEditor.getValue());
            if (window.VoyagerBootstrapCompat) {
                window.VoyagerBootstrapCompat.hideModal(modal);
            } else {
                // Fallback if compat not loaded
                modal.classList.remove('in');
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) backdrop.remove();
            }
        };

        if (window.VoyagerBootstrapCompat) {
            window.VoyagerBootstrapCompat.showModal(modal);
        }
        
        setTimeout(() => {
            aceEditor.resize();
        }, 200);
    }
};

const getConfig = function(options = {}) {

    const baseTinymceConfig = {
        menubar: false,
        selector: 'textarea.richTextBox',
        base_url: getDefaultBaseUrl(),
        skin: 'oxide',
        min_height: 200,
        resize: true,
        plugins: 'link image table lists', // Removed 'code' to avoid conflicts if any, but we override button
        extended_valid_elements : 'input[id|name|value|type|class|style|required|placeholder|autocomplete|onclick]',
        relative_urls: false,
        remove_script_host: true,
        convert_urls: true,
        indent: true,
        apply_source_formatting: true,
        end_container_on_empty_block: true,
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
            
            editor.ui.registry.addButton('code', {
                icon: 'sourcecode',
                tooltip: 'Source code',
                onAction: function () {
                    openAceModal(editor);
                }
            });
        }
    };

    return Object.assign({}, baseTinymceConfig, options);
}

export default { getConfig };
