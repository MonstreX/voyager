let tinyMcePromise;

const loadScriptTag = (src) => new Promise((resolve, reject) => {
    if (typeof document === 'undefined') {
        reject(new Error('TinyMCE loader requires a browser environment'));
        return;
    }

    const existing = document.querySelector(`script[data-voyager-src="${src}"]`);
    if (existing) {
        if (existing.dataset.loaded === 'true') {
            resolve();
        } else {
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error(`Failed to load ${src}`)), { once: true });
        }
        return;
    }

    const script = document.createElement('script');
    script.dataset.voyagerSrc = src;
    script.src = src;
    script.onload = () => {
        script.dataset.loaded = 'true';
        resolve();
    };
    script.onerror = () => reject(new Error(`Failed to load ${src}`));
    document.head.appendChild(script);
});

const getAssetsBase = () => {
    if (typeof window === 'undefined') {
        return '';
    }

    if (window.voyagerTinyMCEBase) {
        return window.voyagerTinyMCEBase.replace(/\/?$/, '/');
    }

    if (typeof document === 'undefined') {
        return '';
    }

    const meta = document.querySelector('meta[name="assets-path"]');
    const fromMeta = meta ? meta.getAttribute('content') || '' : '';
    return fromMeta.replace(/\/?$/, '/') + 'js/';
};

export const loadVoyagerTinyMCE = () => {
    if (typeof window === 'undefined') {
        return Promise.reject(new Error('TinyMCE requires a browser environment'));
    }

    if (window.tinymce) {
        return Promise.resolve(window.tinymce);
    }

    if (!tinyMcePromise) {
        const basePath = getAssetsBase();
        const pluginFiles = [
            'themes/silver/theme.min.js',
            'plugins/link/plugin.min.js',
            'plugins/image/plugin.min.js',
            'plugins/code/plugin.min.js',
            'plugins/table/plugin.min.js',
            'plugins/lists/plugin.min.js'
        ];

        tinyMcePromise = loadScriptTag(`${basePath}tinymce/tinymce.min.js`)
            .then(() => Promise.all(pluginFiles.map(file => loadScriptTag(`${basePath}${file}`))))
            .then(() => {
                if (!window.tinymce) {
                    throw new Error('TinyMCE did not expose a global instance');
                }
                return window.tinymce;
            });
    }

    return tinyMcePromise;
};
