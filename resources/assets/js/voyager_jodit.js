import { Jodit } from 'jodit';
import 'jodit/esm/plugins/source/source.js';
import 'jodit/es2021/jodit.min.css';

// Import Ace Editor (shared with voyager_ace_editor.js via Vite deduplication)
import ace from 'ace-builds/src-noconflict/ace';
import 'ace-builds/src-noconflict/mode-html';
import 'ace-builds/src-noconflict/theme-monokai';

// Make Ace available globally for Jodit
window.ace = ace;

export function initJodit(selector, options = {}) {
    const elements = document.querySelectorAll(selector);
    
    elements.forEach((el) => {
        if (el.jodit || el.closest('.jodit-container')) return; 

        const defaults = {
            height: 400,
            toolbarAdaptive: false,
            sourceEditor: 'ace',
            sourceEditorNativeOptions: {
                theme: 'ace/theme/monokai',
                mode: 'ace/mode/html',
                showGutter: true,
                showPrintMargin: false,
                highlightActiveLine: true,
                wrap: true,
                useWorker: false
            },
            buttons: [
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'ul', 'ol', '|',
                'font', 'fontsize', 'brush', 'paragraph', '|',
                'image', 'video', 'table', 'link', '|',
                'align', 'undo', 'redo', '|',
                'hr', 'eraser', 'copyformat', 'fullsize', '|',
                'source'
            ],
            uploader: {
                url: options.upload_url || '/admin/upload',
                format: 'json',
                imagesExtensions: ['jpg', 'png', 'jpeg', 'gif', 'webp'],
                prepareData: function (data) {
                    const token = document.querySelector('meta[name="csrf-token"]');
                    if (token) {
                        data.append('_token', token.getAttribute('content'));
                    }
                    if (options.type_slug) {
                        data.append('type_slug', options.type_slug);
                    }
                    return data;
                },
                isSuccess: function (resp) {
                    return typeof resp === 'string' && resp.length > 0;
                },
                process: function (resp) {
                    return {
                        files: [resp],
                        isImages: [true],
                        path: '',
                        baseurl: '',
                        error: 0,
                        msg: ''
                    };
                },
                defaultHandlerError: function (resp) {
                    this.jodit.alert('Upload failed');
                },
                error: function (e) {
                    this.jodit.alert(e.message || 'Upload error');
                }
            },
            language: document.documentElement.lang || 'en',
        };

        const config = Object.assign({}, defaults, options);
        if (options.uploader) {
            config.uploader = Object.assign({}, defaults.uploader, options.uploader);
        }

        const editor = Jodit.make(el, config);
        el.jodit = editor;
    });
}
