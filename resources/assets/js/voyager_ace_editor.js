import ace from 'ace-builds/src-noconflict/ace';
import 'ace-builds/src-noconflict/mode-json'; // Common modes
import 'ace-builds/src-noconflict/mode-html';
import 'ace-builds/src-noconflict/mode-php';
import 'ace-builds/src-noconflict/mode-javascript';
import 'ace-builds/src-noconflict/theme-monokai';
import 'ace-builds/src-noconflict/theme-github';

// Expose globally for legacy scripts if needed
window.ace = ace;

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

    Array.from(elements).forEach(element => {
        if (element.classList.contains('ace_initialized')) return;

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