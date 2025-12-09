/**
 * Vue Bundle
 *
 * This bundle contains:
 * - Vue 3 framework
 * - Vue bridge (global helpers for Blade templates)
 * - Admin menu component
 *
 * Loaded on all admin pages.
 */

import AdminMenu from './components/admin_menu.vue';
import { createApp } from 'vue';

// Voyager.ready.vue Promise already initialized in master.blade.php <head>
// Just get the resolver
const resolveVueReady = window.__resolveVueReady;

// Registry to keep compatibility with legacy Vue component registration
const voyagerComponentRegistry = {};
const voyagerActiveApps = [];

const applyVoyagerComponents = (appInstance) => {
    Object.keys(voyagerComponentRegistry).forEach((name) => {
        appInstance.component(name, voyagerComponentRegistry[name]);
    });
    return appInstance;
};

// Setup Vue 3 global helpers for blade templates
const createVoyagerVueApp = function (rootComponent = {}) {
    const shouldClone = rootComponent && typeof rootComponent === 'object';
    const resolvedRootComponent = shouldClone ? { ...rootComponent } : rootComponent;
    const appInstance = applyVoyagerComponents(createApp(resolvedRootComponent));
    const originalMount = appInstance.mount;

    appInstance.mount = function (target, ...args) {
        const mountTarget = typeof target === 'string' ? document.querySelector(target) : target;

        if (
            mountTarget &&
            resolvedRootComponent &&
            typeof resolvedRootComponent === 'object' &&
            !resolvedRootComponent.render &&
            !resolvedRootComponent.template
        ) {
            resolvedRootComponent.template = mountTarget.innerHTML;
        }

        return originalMount.call(this, target, ...args);
    };

    voyagerActiveApps.push(appInstance);
    return appInstance;
};

window.__vueGlobalApp = null;

const registerVoyagerComponent = function (name, definition) {
    voyagerComponentRegistry[name] = definition;
    voyagerActiveApps.forEach((appInstance) => {
        appInstance.component(name, definition);
    });
};

const mountVoyagerVueApp = function (selector, rootComponent = {}) {
    const mountTargets = typeof selector === 'string'
        ? document.querySelectorAll(selector)
        : selector instanceof Element
            ? [selector]
            : selector instanceof NodeList
                ? selector
                : [];

    let mountedApp = null;

    mountTargets.forEach((target) => {
        if (!target) {
            return;
        }
        mountedApp = window.createVueApp(rootComponent);
        mountedApp.mount(target);
    });

    return mountedApp;
};

const attachLegacyGlobal = (name, fn, message) => {
    if (typeof window === 'undefined') {
        return;
    }
    const warnedFlag = `__voyagerWarned_${name}`;
    window[name] = function (...args) {
        if (typeof console !== 'undefined' && typeof console.warn === 'function' && !window[warnedFlag]) {
            window[warnedFlag] = true;
            console.warn(message);
        }
        return fn(...args);
    };
};

if (typeof window !== 'undefined') {
    window.Voyager = window.Voyager || {};
    window.Voyager.vue = Object.assign({}, window.Voyager.vue, {
        createApp: createVoyagerVueApp,
        registerComponent: registerVoyagerComponent,
        mountApp: mountVoyagerVueApp
    });

    attachLegacyGlobal('createVueApp', createVoyagerVueApp, '[Voyager] window.createVueApp is deprecated. Use Voyager.vue.createApp instead.');
    attachLegacyGlobal('VueRegisterComponent', registerVoyagerComponent, '[Voyager] window.VueRegisterComponent is deprecated. Use Voyager.vue.registerComponent instead.');
    attachLegacyGlobal('VueMountApp', mountVoyagerVueApp, '[Voyager] window.VueMountApp is deprecated. Use Voyager.vue.mountApp instead.');
}

// Initialize admin menu when DOM is ready
const initAdminMenu = () => {
    if (document.getElementById('adminmenu')) {
        const adminMenuApp = createApp({});
        adminMenuApp.component('admin-menu', AdminMenu);
        adminMenuApp.mount('#adminmenu');
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminMenu, { once: true });
} else {
    initAdminMenu();
}

export const createVueApp = createVoyagerVueApp;
export const registerComponent = registerVoyagerComponent;
export const mountApp = mountVoyagerVueApp;

// Signal that Vue bundle is ready
resolveVueReady();
document.dispatchEvent(new CustomEvent('voyager:vue-ready'));
