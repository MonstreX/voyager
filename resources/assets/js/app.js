import './modules/csrf';
import '../sass/app.scss';

// Core
import { initBootstrapCompat, showModal, hideModal, initTooltips } from './core/bootstrap-compat';
import { initGlobalEvents } from './core/events';
import { voyagerEvents } from './core/event-bus';

// Components
import { initDatePickers, destroyDatePicker, refreshDatePicker, subscribeToEvents as subscribeDatePickers } from './components/datepicker';
import { initToggleSwitches, destroyToggleSwitch, refreshToggleSwitch, subscribeToEvents as subscribeToggles } from './components/toggle';
import { initVoyagerSelects, refreshVoyagerSelect, setVoyagerSelectOptions, subscribeToEvents as subscribeSelects } from './components/select';
import { initNestable, serializeNestable } from './components/nestable';
import { initMatchHeight, subscribeToEvents as subscribeMatchHeight } from './components/match-height';
import { initMarkdownEditor, subscribeToEvents as subscribeMarkdown } from './components/editor-markdown';

// Modules
import VoyagerToaster from './modules/toaster';
import { initSlugifyFields, subscribeToEvents as subscribeSlugify } from './modules/slugify';
import SimpleTable, { initSimpleTables } from './modules/simple-table';
import { initStickyActionPanels, subscribeToEvents as subscribeStickyPanel } from './modules/sticky-action-panel';
import { initTabs, subscribeToEvents as subscribeTabs } from './modules/tabs';

// Legacy / Vendor
import './multilingual';
import * as helpers from './helpers.js';
import Cropper from 'cropperjs';
import Sortable from 'sortablejs';

// Voyager namespace and ready Promises are initialized in master.blade.php <head>
// Get the resolvers that were created there
const resolveAppReady = window.__resolveAppReady;
const rejectVueReady = window.__rejectVueReady || (() => {});
const rejectEditorsReady = window.__rejectEditorsReady || (() => {});

const assetsMeta = typeof document !== 'undefined'
    ? document.head.querySelector('meta[name="assets-path"]')
    : null;
const assetsBase = assetsMeta ? assetsMeta.getAttribute('content') : '/vendor/voyager/';
const buildAssetUrl = (relativePath = '') => {
    const normalizedBase = assetsBase.replace(/\/?$/, '/');
    const normalizedPath = (relativePath || '').replace(/^\//, '');
    return normalizedBase + normalizedPath;
};

const loadedStylesheets = new Map();

const ensureStylesheet = (relativePath, key) => {
    if (typeof document === 'undefined') {
        return Promise.resolve();
    }
    const cacheKey = key || relativePath;
    if (loadedStylesheets.has(cacheKey)) {
        return loadedStylesheets.get(cacheKey);
    }
    const href = buildAssetUrl(relativePath);
    const existing = document.head.querySelector(`link[data-voyager-style="${cacheKey}"]`) ||
        document.head.querySelector(`link[href="${href}"]`);
    if (existing) {
        const promise = Promise.resolve(existing);
        loadedStylesheets.set(cacheKey, promise);
        return promise;
    }

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    link.setAttribute('data-voyager-style', cacheKey);

    const promise = new Promise((resolve, reject) => {
        link.onload = () => resolve(link);
        link.onerror = (event) => {
            console.error(`[Voyager] Failed to load stylesheet ${relativePath}`, event);
            reject(event);
        };
    });

    document.head.appendChild(link);
    loadedStylesheets.set(cacheKey, promise);
    return promise;
};

const memoizeDynamicImport = (relativePath, { label, reject }) => {
    let promise = null;
    return () => {
        if (!promise) {
            const url = buildAssetUrl(relativePath);
            promise = import(url).catch((error) => {
                const message = `[Voyager] Failed to load ${label || relativePath}`;
                console.error(message, error);
                if (typeof reject === 'function') {
                    reject(error);
                }
                throw error;
            });
        }
        return promise;
    };
};

const loadVueBundle = memoizeDynamicImport('js/vue-bundle.js', {
    label: 'Vue bundle',
    reject: rejectVueReady
});

const loadEditorsBundle = memoizeDynamicImport('js/editors.js', {
    label: 'Editors bundle',
    reject: rejectEditorsReady
});

const loadEditorsAssets = () => {
    const cssPromise = ensureStylesheet('css/editors.css', 'voyager-editors-css')
        .catch((error) => {
            // Continue even if CSS failed to load, error already logged
            return error;
        });
    return Promise.all([cssPromise, loadEditorsBundle()]).then(([, module]) => module);
};

const resolveVueApi = (module) => {
    const voyagerVue = (window.Voyager && window.Voyager.vue) || {};
    const fallback = {
        createApp: typeof window.createVueApp === 'function' ? window.createVueApp : undefined,
        registerComponent: typeof window.VueRegisterComponent === 'function' ? window.VueRegisterComponent : undefined,
        mountApp: typeof window.VueMountApp === 'function' ? window.VueMountApp : undefined
    };

    return {
        createApp: module && module.createVueApp ? module.createVueApp : voyagerVue.createApp || fallback.createApp,
        registerComponent: module && module.registerComponent ? module.registerComponent : voyagerVue.registerComponent || fallback.registerComponent,
        mountApp: module && module.mountApp ? module.mountApp : voyagerVue.mountApp || fallback.mountApp
    };
};

const startDomObserver = () => {
    if (typeof window === 'undefined' || typeof MutationObserver === 'undefined') {
        return null;
    }

    const root = document.querySelector('.app-container') || document.body;
    if (!root) {
        return null;
    }

    let scheduled = false;
    const pendingNodes = new Set();
    const flush = () => {
        scheduled = false;
        const targets = Array.from(pendingNodes);
        pendingNodes.clear();
        if (!targets.length) {
            voyagerEvents.emit('dom:updated', document);
            return;
        }
        voyagerEvents.emit('dom:updated', targets.length === 1 ? targets[0] : targets);
    };
    const scheduleEmit = () => {
        if (scheduled) {
            return;
        }
        scheduled = true;
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(flush);
        } else {
            setTimeout(flush, 60);
        }
    };

    const observer = new MutationObserver((mutationList) => {
        let added = false;
        mutationList.forEach((mutation) => {
            if (!mutation.addedNodes || !mutation.addedNodes.length) {
                return;
            }
            mutation.addedNodes.forEach((node) => {
                if (node && node.nodeType === Node.ELEMENT_NODE) {
                    pendingNodes.add(node);
                    added = true;
                }
            });
        });
        if (added) {
            scheduleEmit();
        }
    });

    observer.observe(root, { childList: true, subtree: true });
    return observer;
};

// Event system
window.Voyager.events = voyagerEvents;
window.Voyager.emitDomUpdated = (container = document) => {
    voyagerEvents.emit('dom:updated', container || document);
};
window.Voyager.loadVue = () => loadVueBundle();
window.Voyager.loadEditors = () => loadEditorsAssets();
window.Voyager.withVue = (callback) => {
    if (!callback || typeof callback !== 'function') {
        return Promise.resolve();
    }
    return window.Voyager.loadVue()
        .then((module) => {
            const api = resolveVueApi(module);
            if (!api.createApp) {
                throw new Error('[Voyager] Vue API unavailable.');
            }
            return callback(api);
        })
        .catch((error) => {
            console.error('[Voyager] Failed to load Vue bundle', error);
            throw error;
        });
};

// Core utilities
window.Voyager.helpers = helpers;
window.Voyager.toastr = new VoyagerToaster();
window.Voyager.Cropper = Cropper;
window.Voyager.Sortable = Sortable;

// Component initialization functions
window.Voyager.init = {
    bootstrap: initBootstrapCompat,
    tooltips: initTooltips,
    datepickers: initDatePickers,
    toggles: initToggleSwitches,
    selects: initVoyagerSelects,
    matchHeight: initMatchHeight,
    nestable: initNestable,
    markdown: initMarkdownEditor,
    simpleTables: initSimpleTables,
    slugify: initSlugifyFields,
    tabs: initTabs
};

// Component destroy/refresh functions
window.Voyager.destroy = {
    datepicker: destroyDatePicker,
    toggle: destroyToggleSwitch
};

window.Voyager.refresh = {
    datepicker: refreshDatePicker,
    toggle: refreshToggleSwitch,
    select: refreshVoyagerSelect
};

// Bootstrap utilities
window.Voyager.bootstrap = {
    init: initBootstrapCompat,
    showModal,
    hideModal
};

// Other utilities
window.Voyager.serializeNestable = serializeNestable;
window.Voyager.setSelectOptions = setVoyagerSelectOptions;
window.Voyager.SimpleTable = SimpleTable;

// Subscribe components to dom:updated event
subscribeToggles(voyagerEvents);
subscribeDatePickers(voyagerEvents);
subscribeSelects(voyagerEvents);
subscribeMatchHeight(voyagerEvents);
subscribeMarkdown(voyagerEvents);
subscribeSlugify(voyagerEvents);
subscribeStickyPanel(voyagerEvents);
subscribeTabs(voyagerEvents);

// Legacy Global Exports (keep for backward compatibility)
window.VoyagerBootstrapCompat = { init: initBootstrapCompat, showModal, hideModal };
window.VoyagerInitTooltips = initTooltips;
window.VoyagerInitDatePickers = initDatePickers;
window.VoyagerDestroyDatePicker = destroyDatePicker;
window.VoyagerRefreshDatePicker = refreshDatePicker;
window.VoyagerInitToggles = initToggleSwitches;
window.VoyagerDestroyToggle = destroyToggleSwitch;
window.VoyagerRefreshToggle = refreshToggleSwitch;
window.VoyagerInitSelects = initVoyagerSelects;
window.VoyagerSelectRefresh = refreshVoyagerSelect;
window.VoyagerSelectSetOptions = setVoyagerSelectOptions;
window.VoyagerInitMatchHeight = initMatchHeight;
window.VoyagerInitNestable = initNestable;
window.VoyagerSerializeNestable = serializeNestable;
window.VoyagerSimpleTable = SimpleTable;
window.VoyagerInitSimpleTables = initSimpleTables;
window.VoyagerInitSlugify = initSlugifyFields;
window.helpers = helpers;
window.Cropper = Cropper;
window.toastr = new VoyagerToaster();
window.Sortable = Sortable;


document.addEventListener('DOMContentLoaded', () => {
    // Init Core
    initBootstrapCompat();
    initGlobalEvents();

    // Init Components
    initSimpleTables();
    initToggleSwitches();
    initDatePickers();
    initVoyagerSelects();
    initMatchHeight();
    initMarkdownEditor();
    initStickyActionPanels();
    initTabs();

    // Init Modules
    if (window.VoyagerInitSlugify) {
        window.VoyagerInitSlugify('.side-body input[data-slug-origin]');
    }

    // Autoload Vue bundle when admin Vue hooks are present
    if (document.querySelector('#adminmenu, [data-voyager-vue-root]')) {
        window.Voyager.loadVue().catch(() => {});
    }

    // Emit dom:updated event for initial page load
    // Components can subscribe to this for dynamic reinitialization
    voyagerEvents.emit('dom:updated', document);

    startDomObserver();
});

// Signal that app bundle is ready
resolveAppReady();
document.dispatchEvent(new CustomEvent('voyager:app-ready'));
