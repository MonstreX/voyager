const assetsMeta = typeof document !== 'undefined'
    ? document.head.querySelector('meta[name="assets-path"]')
    : null;
const assetsBase = assetsMeta ? assetsMeta.getAttribute('content').replace(/\/?$/, '/') : '/vendor/voyager/';

function voyagerLoadModule(relativePath, cacheKey, rejectHandler) {
    const normalizedPath = (relativePath || '').replace(/^\//, '');
    const key = '__voyagerModule_' + cacheKey;
    if (window[key]) {
        return window[key];
    }
    const moduleUrl = assetsBase + normalizedPath;
    window[key] = import(moduleUrl).catch(function(error) {
        console.error('[Voyager] Failed to load ' + normalizedPath, error);
        if (typeof rejectHandler === 'function') {
            rejectHandler(error);
        }
        throw error;
    });
    return window[key];
}

window.Voyager = window.Voyager || {};
window.Voyager.ready = window.Voyager.ready || {};

window.Voyager.ready.app = new Promise(function(resolve, reject) {
    window.__resolveAppReady = resolve;
    window.__rejectAppReady = reject;
});
window.Voyager.ready.vue = new Promise(function(resolve, reject) {
    window.__resolveVueReady = resolve;
    window.__rejectVueReady = reject;
});
window.Voyager.ready.editors = new Promise(function(resolve, reject) {
    window.__resolveEditorsReady = resolve;
    window.__rejectEditorsReady = reject;
});

if (typeof window.Voyager.loadVue !== 'function') {
    window.Voyager.loadVue = function() {
        return voyagerLoadModule('js/vue-bundle.js', 'vue', window.__rejectVueReady);
    };
}

if (typeof window.Voyager.loadEditors !== 'function') {
    window.Voyager.loadEditors = function() {
        return voyagerLoadModule('js/editors.js', 'editors', window.__rejectEditorsReady);
    };
}

if (typeof window.Voyager.withVue !== 'function') {
    window.Voyager.withVue = function(callback) {
        return window.Voyager.loadVue().then(function() {
            var api = (window.Voyager && window.Voyager.vue) ? window.Voyager.vue : null;
            if (!api) {
                throw new Error('[Voyager] Voyager.vue API unavailable.');
            }
            if (typeof callback === 'function') {
                callback(api);
            }
            return api;
        }).catch(function(error) {
            console.error('[Voyager] Failed to prepare Vue helpers', error);
            throw error;
        });
    };
}
