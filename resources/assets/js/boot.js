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
            var api = (window.Voyager && window.Voyager.vue) ? window.Voyager.vue : {
                createApp: window.createVueApp,
                registerComponent: window.VueRegisterComponent,
                mountApp: window.VueMountApp
            };
            var result = {
                createApp: api.createApp || window.createVueApp || function() {},
                registerComponent: api.registerComponent || window.VueRegisterComponent || function() {},
                mountApp: api.mountApp || window.VueMountApp || function() {}
            };
            if (typeof callback === 'function') {
                callback(result);
            }
            return result;
        }).catch(function(error) {
            console.error('[Voyager] Failed to prepare Vue helpers', error);
            throw error;
        });
    };
}

window.__voyagerDeprecationWarned = window.__voyagerDeprecationWarned || {};
function warnDeprecated(api, replacement) {
    if (window.__voyagerDeprecationWarned[api]) {
        return;
    }
    window.__voyagerDeprecationWarned[api] = true;
    if (window.console && typeof window.console.warn === 'function') {
        window.console.warn('[Voyager] ' + api + ' is deprecated. Use ' + replacement + ' instead.');
    }
}

window.whenAppReady = function(callback) {
    warnDeprecated('whenAppReady()', 'Voyager.ready.app.then');
    window.Voyager.ready.app.then(callback);
};

window.whenVueReady = function(callback) {
    warnDeprecated('whenVueReady()', 'Voyager.loadVue().then');
    window.Voyager.withVue(function() {
        if (typeof callback === 'function') {
            callback();
        }
    });
};

window.whenEditorsReady = function(callback) {
    warnDeprecated('whenEditorsReady()', 'Voyager.loadEditors().then');
    var loader = window.Voyager && typeof window.Voyager.loadEditors === 'function'
        ? window.Voyager.loadEditors()
        : Promise.resolve();
    loader.then(function() {
        return window.Voyager.ready.editors;
    }).then(callback);
};

