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
import { createApp as vueCreateApp } from 'vue';

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
    const appInstance = applyVoyagerComponents(vueCreateApp(resolvedRootComponent));
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
        mountedApp = createVoyagerVueApp(rootComponent);
        mountedApp.mount(target);
    });

    return mountedApp;
};

if (typeof window !== 'undefined') {
    window.Voyager = window.Voyager || {};
    window.Voyager.vue = Object.assign({}, window.Voyager.vue, {
        createApp: createVoyagerVueApp,
        registerComponent: registerVoyagerComponent,
        mountApp: mountVoyagerVueApp
    });
}

// Initialize admin menu when DOM is ready
const initAdminMenu = () => {
    if (document.getElementById('adminmenu')) {
        const adminMenuApp = vueCreateApp({});
        adminMenuApp.component('admin-menu', AdminMenu);
        adminMenuApp.mount('#adminmenu');
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminMenu, { once: true });
} else {
    initAdminMenu();
}

export const createApp = createVoyagerVueApp;
export const registerComponent = registerVoyagerComponent;
export const mountApp = mountVoyagerVueApp;

// Signal that Vue bundle is ready
resolveVueReady();
