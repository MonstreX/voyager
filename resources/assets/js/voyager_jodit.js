import { Jodit } from 'jodit';
import 'jodit/es2021/jodit.min.css';

const initJodit = (selector, options = {}) => {
    const elements = document.querySelectorAll(selector);
    
    elements.forEach((el) => {
        // Check if already initialized to avoid double init
        if (el.closest('.jodit-container')) return; 

        const defaults = {
            minHeight: 400,
            toolbarAdaptive: false,
            buttons: 'source,|,bold,italic,underline,strikethrough,|,ul,ol,|,font,fontsize,brush,paragraph,|,image,video,table,link,|,align,undo,redo,|,hr,eraser,copyformat,fullsize',
            uploader: {
                insertImageAsBase64URI: true, // Temporary fallback until backend route is ready
            },
            language: document.documentElement.lang || 'en',
        };

        // Merge defaults with provided options
        const config = Object.assign({}, defaults, options);

        const editor = Jodit.make(el, config);
        
        // Save instance to element for potential future access
        el.jodit = editor;
    });
};

// Expose to global scope for use in Blade templates
window.VoyagerInitJodit = initJodit;
window.Jodit = Jodit; // Expose class just in case
