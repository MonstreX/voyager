/**
 * Editors Bundle
 *
 * This bundle contains:
 * - Jodit WYSIWYG editor
 * - Ace Code editor
 *
 * Only loaded on pages with editor fields.
 */

// Jodit Editor
import { Jodit } from 'jodit';
import 'jodit/esm/plugins/source/source.js';
import 'jodit/es2021/jodit.min.css';

// Ace Editor
import ace from 'ace-builds/src-noconflict/ace';
import 'ace-builds/src-noconflict/mode-html';
import 'ace-builds/src-noconflict/mode-json';
import 'ace-builds/src-noconflict/mode-php';
import 'ace-builds/src-noconflict/mode-javascript';
import 'ace-builds/src-noconflict/theme-monokai';
import 'ace-builds/src-noconflict/theme-github';

// Make Ace available globally (required by Jodit source mode)
window.ace = ace;

// Voyager.ready.editors Promise already initialized in master.blade.php <head>
// Just get the resolver
const resolveEditorsReady = window.__resolveEditorsReady;

/**
 * Initialize Jodit editor
 */
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

/**
 * Initialize Ace editors
 */
export function initAceEditors(container = document) {
    const elements = container.getElementsByClassName("ace_editor");

    // Setup base config once
    const assetsMeta = document.querySelector('meta[name="assets-path"]');
    const assetsBase = assetsMeta ? assetsMeta.getAttribute('content') : '';
    const aceBasePath = (assetsBase.replace(/\/?$/, '/') + 'js/ace/libs').replace(/\/?$/, '/');

    ace.config.set("basePath", aceBasePath);
    ace.config.set("workerPath", aceBasePath);
    ace.config.set("modePath", aceBasePath);
    ace.config.set("themePath", aceBasePath);

    // Disable web workers to avoid loading issues
    ace.config.set("useWorker", false);

    Array.from(elements).forEach(element => {
        if (element.classList.contains('ace_initialized')) return;

        // Skip elements without ID (may be already initialized by inline scripts)
        if (!element.id || element.id.trim() === '') {
            return;
        }

        const editor = ace.edit(element.id);
        const textarea = document.getElementById(element.id + '_textarea');

        // Set Theme
        const theme = element.getAttribute('data-theme') || 'monokai';
        editor.setTheme("ace/theme/" + theme);

        // Set Mode
        const mode = element.getAttribute('data-language');
        if (mode) {
            editor.getSession().setMode("ace/mode/" + mode);
        }

        // Disable worker for this session too
        editor.getSession().setUseWorker(false);

        // Sync with textarea
        editor.on('change', function() {
            if (textarea) {
                textarea.value = editor.getValue();
            }
        });

        // Mark as initialized
        element.classList.add('ace_initialized');
    });
}

// Expose globally for Blade templates
window.VoyagerInitJodit = initJodit;
window.VoyagerInitAceEditors = initAceEditors;

// Auto-initialize Ace editors on DOM ready (for code_editor fields)
document.addEventListener('DOMContentLoaded', () => {
    initAceEditors();
});

// Signal that editors bundle is ready
resolveEditorsReady();
document.dispatchEvent(new CustomEvent('voyager:editors-ready'));
