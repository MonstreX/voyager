import './jquery-first';
import '../sass/app.scss';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

import SimpleTable from './modules/simple-table';
window.VoyagerSimpleTable = SimpleTable;

const loadLegacyPlugin = (source, sourceName) => {
    if (typeof window === 'undefined') {
        return;
    }

    const runner = new Function(
        'window',
        'document',
        '$',
        'jQuery',
        'define',
        'exports',
        'module',
        'undefined',
        `${source}\n//# sourceURL=${sourceName}`
    );

    runner(window, document, window.jQuery, window.jQuery, undefined, undefined, undefined);
};

import select2Source from './vendor/select2.full.js?raw';
if (typeof window !== 'undefined' && window.jQuery) {
    loadLegacyPlugin(select2Source, 'select2.full.js');
    if (window.jQuery.fn && window.jQuery.fn.select2) {
        window.Select2 = window.jQuery.fn.select2;
    }
}

import jqueryUiSource from './vendor/jquery-ui.js?raw';
loadLegacyPlugin(jqueryUiSource, 'jquery-ui.js');

import nestableSource from './vendor/jquery.nestable.js?raw';
loadLegacyPlugin(nestableSource, 'jquery.nestable.js');

import matchHeightSource from './vendor/jquery.matchHeight.js?raw';
loadLegacyPlugin(matchHeightSource, 'jquery.matchHeight.js');

const initSimpleTables = () => {
    if (typeof document === 'undefined') {
        return;
    }
    document.querySelectorAll('[data-simple-table]').forEach((table) => {
        if (table.__voyagerSimpleTable) {
            return;
        }
        try {
            const rawConfig = table.getAttribute('data-simple-table');
            const options = rawConfig ? JSON.parse(rawConfig) : {};
            table.__voyagerSimpleTable = new SimpleTable(table, options);
        } catch (error) {
            console.error('Voyager simple table init failed', error);
        }
    });
};

window.VoyagerInitSimpleTables = initSimpleTables;

const resolveToggleElements = (target) => {
    if (!target || target === document) {
        return Array.from(document.querySelectorAll('.toggleswitch, input[data-toggle^="toggle"]'));
    }

    if (typeof target === 'string') {
        return Array.from(document.querySelectorAll(target));
    }

    if (target instanceof Element) {
        const directMatch = target.matches('.toggleswitch, input[data-toggle^="toggle"]') ? [target] : [];
        return directMatch.concat(
            Array.from(target.querySelectorAll('.toggleswitch, input[data-toggle^="toggle"]'))
        );
    }

    if (target instanceof NodeList || Array.isArray(target)) {
        const elements = [];
        Array.from(target).forEach((node) => {
            if (!node) {
                return;
            }
            elements.push(...resolveToggleElements(node));
        });
        return elements;
    }

    return [];
};

const getToggleOption = (input, attr, fallback) => {
    const value = input.getAttribute(attr);
    return value !== null && value !== undefined && value !== '' ? value : fallback;
};

const getToggleSizeClass = (size) => {
    switch (size) {
        case 'large':
            return 'btn-lg';
        case 'small':
            return 'btn-sm';
        case 'mini':
        case 'xs':
            return 'btn-xs';
        default:
            return '';
    }
};

const buildToggleMarkup = (input, options) => {
    const onClass = `btn-${options.onstyle}`;
    const offClass = `btn-${options.offstyle}`;
    const sizeClass = getToggleSizeClass(options.size);
    const wrapper = document.createElement('div');
    wrapper.className = `toggle btn ${input.checked ? onClass : `${offClass} off`}`.trim();
    wrapper.dataset.toggle = 'toggle';
    if (sizeClass) {
        wrapper.classList.add(sizeClass);
    }
    if (options.style) {
        options.style.split(' ').filter(Boolean).forEach((cls) => wrapper.classList.add(cls));
    }
    wrapper.setAttribute('role', 'switch');

    const group = document.createElement('div');
    group.className = 'toggle-group';

    const toggleOn = document.createElement('label');
    toggleOn.className = ['btn', 'toggle-on', onClass, sizeClass].filter(Boolean).join(' ');
    toggleOn.innerHTML = options.on;

    const toggleOff = document.createElement('label');
    toggleOff.className = ['btn', 'toggle-off', offClass, sizeClass].filter(Boolean).join(' ');
    toggleOff.innerHTML = options.off;

    const handle = document.createElement('span');
    handle.className = ['toggle-handle', 'btn', 'btn-default', sizeClass].filter(Boolean).join(' ');

    group.append(toggleOn, toggleOff, handle);
    wrapper.appendChild(group);

    return {
        wrapper,
        group,
        toggleOn,
        toggleOff,
        handle,
        onClass,
        offClass
    };
};

const initToggleSwitches = (target) => {
    const elements = resolveToggleElements(target);
    const seen = new Set();

    elements.forEach((input) => {
        if (!(input instanceof HTMLInputElement) || input.type !== 'checkbox') {
            return;
        }
        if (input.dataset.voyagerToggleInitialized === 'true') {
            return;
        }
        if (seen.has(input)) {
            return;
        }
        seen.add(input);

        const options = {
            on: getToggleOption(input, 'data-on', 'On'),
            off: getToggleOption(input, 'data-off', 'Off'),
            onstyle: getToggleOption(input, 'data-onstyle', 'primary'),
            offstyle: getToggleOption(input, 'data-offstyle', 'default'),
            size: getToggleOption(input, 'data-size', 'normal'),
            style: getToggleOption(input, 'data-style', ''),
            width: getToggleOption(input, 'data-width', null),
            height: getToggleOption(input, 'data-height', null)
        };

        const {
            wrapper,
            toggleOn,
            toggleOff,
            handle,
            onClass,
            offClass
        } = buildToggleMarkup(input, options);

        const parent = input.parentNode;
        if (!parent) {
            return;
        }
        parent.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.style.display = 'none';
        input.dataset.voyagerToggleInitialized = 'true';

        const updateState = () => {
            const checked = input.checked;
            if (checked) {
                wrapper.classList.add(onClass);
                wrapper.classList.remove(offClass, 'off');
                toggleOn.classList.add('active');
                toggleOff.classList.remove('active');
            } else {
                wrapper.classList.remove(onClass);
                wrapper.classList.add(offClass, 'off');
                toggleOn.classList.remove('active');
                toggleOff.classList.add('active');
            }
            if (input.disabled) {
                wrapper.classList.add('disabled');
                wrapper.setAttribute('aria-disabled', 'true');
                wrapper.tabIndex = -1;
            } else {
                wrapper.classList.remove('disabled');
                wrapper.removeAttribute('aria-disabled');
                wrapper.tabIndex = 0;
            }
            wrapper.setAttribute('aria-checked', checked ? 'true' : 'false');
        };

        const setChecked = () => {
            if (input.disabled) {
                return;
            }
            input.checked = !input.checked;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const adjustDimensions = () => {
            if (options.width) {
                wrapper.style.width = /^\d+$/.test(String(options.width))
                    ? `${options.width}px`
                    : options.width;
            } else {
                const onWidth = toggleOn.getBoundingClientRect().width;
                const offWidth = toggleOff.getBoundingClientRect().width;
                const handleWidth = handle.getBoundingClientRect().width || 0;
                const width = Math.max(onWidth, offWidth) + handleWidth / 2;
                wrapper.style.width = `${Math.ceil(width)}px`;
            }

            if (options.height) {
                wrapper.style.height = /^\d+$/.test(String(options.height))
                    ? `${options.height}px`
                    : options.height;
                toggleOn.style.lineHeight = toggleOn.offsetHeight + 'px';
                toggleOff.style.lineHeight = toggleOff.offsetHeight + 'px';
            } else {
                const height = Math.max(toggleOn.offsetHeight, toggleOff.offsetHeight);
                wrapper.style.height = `${height}px`;
            }
        };

        wrapper.addEventListener('click', (event) => {
            if (event.target === input) {
                return;
            }
            event.preventDefault();
            setChecked();
        });
        wrapper.addEventListener('keydown', (event) => {
            if (event.key === ' ' || event.key === 'Enter') {
                event.preventDefault();
                setChecked();
            }
        });
        input.addEventListener('change', () => updateState());

        const observer = new MutationObserver((mutations) => {
            const shouldUpdate = mutations.some((mutation) => mutation.attributeName === 'disabled');
            if (shouldUpdate) {
                updateState();
            }
        });
        observer.observe(input, { attributes: true, attributeFilter: ['disabled'] });

        requestAnimationFrame(adjustDimensions);
        updateState();
    });
};

window.VoyagerInitToggles = initToggleSwitches;

const getDefaultFlatpickrConfig = (type) => {
    switch (type) {
        case 'datetime':
            return {
                enableTime: true,
                altInput: true,
                altFormat: 'F j, Y H:i',
                dateFormat: 'Y-m-d\\TH:i'
            };
        case 'time':
            return {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true
            };
        case 'date':
        default:
            return {
                altInput: true,
                altFormat: 'F j, Y',
                dateFormat: 'Y-m-d'
            };
    }
};

const parseFlatpickrOptions = (element) => {
    const attr = element.getAttribute('data-flatpickr') || element.getAttribute('data-datepicker');
    if (!attr) {
        return {};
    }

    try {
        const parsed = JSON.parse(attr);
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch (error) {
        console.warn('Voyager datepicker options JSON parse failed', error, attr);
        return {};
    }
};

const initDatePickers = () => {
    if (typeof document === 'undefined') {
        return;
    }

    const selector = 'input[data-flatpickr], input[data-flatpickr-type], input[data-datepicker]';
    document.querySelectorAll(selector).forEach((input) => {
        if (input.dataset.flatpickrInitialized === 'true') {
            return;
        }

        const userConfig = parseFlatpickrOptions(input);
        const typeAttr = input.getAttribute('data-flatpickr-type') || input.getAttribute('data-datepicker-type');
        const guessedType = typeAttr || (userConfig.enableTime ? (userConfig.noCalendar ? 'time' : 'datetime') : 'date');
        const defaults = getDefaultFlatpickrConfig(guessedType);
        const config = {
            ...defaults,
            ...userConfig
        };

        if (typeof config.allowInput === 'undefined') {
            config.allowInput = true;
        }
        if (typeof config.disableMobile === 'undefined') {
            config.disableMobile = true;
        }

        try {
            flatpickr(input, config);
            input.dataset.flatpickrInitialized = 'true';
        } catch (error) {
            console.error('Voyager flatpickr init failed', error);
        }
    });
};

window.VoyagerInitDatePickers = initDatePickers;

const getSafeEventTarget = (event) => {
    if (!event) {
        return null;
    }
    if (event.target instanceof Element) {
        return event.target;
    }
    return event.target && event.target.parentElement ? event.target.parentElement : null;
};

const getTargetSelector = (trigger) => {
    if (!trigger) {
        return null;
    }
    const rawSelector = trigger.getAttribute('data-target') || trigger.getAttribute('href');
    if (!rawSelector) {
        return null;
    }
    if (rawSelector.startsWith('#') || rawSelector.startsWith('.')) {
        return rawSelector;
    }
    if (rawSelector.indexOf('#') >= 0) {
        return `#${rawSelector.split('#').pop()}`;
    }
    return rawSelector;
};

const findTargetElement = (trigger) => {
    const selector = getTargetSelector(trigger);
    if (!selector) {
        return null;
    }
    try {
        return document.querySelector(selector);
    } catch (error) {
        return null;
    }
};

const dispatchCustomEvent = (element, name) => {
    if (!element) {
        return;
    }
    const event = new CustomEvent(name, { bubbles: true });
    element.dispatchEvent(event);
};

const modalStack = [];
const modalBackdropMap = new Map();

const showModalElement = (modal) => {
    if (!modal || modal.classList.contains('voyager-modal-visible')) {
        return;
    }
    modal.classList.add('voyager-modal-visible');
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('in', 'show');
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade in';
    backdrop.dataset.voyagerModalId = modal.id || '';
    document.body.appendChild(backdrop);
    modalBackdropMap.set(modal, backdrop);
    modalStack.push(modal);
    document.body.classList.add('modal-open');
    dispatchCustomEvent(modal, 'shown.bs.modal');
};

const hideModalElement = (modal) => {
    if (!modal || !modal.classList.contains('voyager-modal-visible')) {
        return;
    }
    if (modal.contains(document.activeElement) && document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
    }
    modal.classList.remove('voyager-modal-visible');
    modal.classList.remove('in', 'show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    const backdrop = modalBackdropMap.get(modal);
    if (backdrop && backdrop.parentNode) {
        backdrop.parentNode.removeChild(backdrop);
    }
    modalBackdropMap.delete(modal);
    const index = modalStack.indexOf(modal);
    if (index !== -1) {
        modalStack.splice(index, 1);
    }
    if (modalStack.length === 0) {
        document.body.classList.remove('modal-open');
    }
    dispatchCustomEvent(modal, 'hidden.bs.modal');
};

const currentModal = () => {
    return modalStack.length ? modalStack[modalStack.length - 1] : null;
};

const dropdownState = {
    openDropdown: null
};

const closeDropdownMenu = (dropdown) => {
    if (!dropdown) {
        return;
    }
    dropdown.classList.remove('open');
    if (dropdown === dropdownState.openDropdown) {
        dropdownState.openDropdown = null;
    }
};

const toggleDropdownFromTrigger = (trigger) => {
    const dropdown = trigger.closest('.dropdown');
    if (!dropdown) {
        return;
    }
    if (dropdown === dropdownState.openDropdown) {
        closeDropdownMenu(dropdown);
        return;
    }
    if (dropdownState.openDropdown) {
        closeDropdownMenu(dropdownState.openDropdown);
    }
    dropdown.classList.add('open');
    dropdownState.openDropdown = dropdown;
};

const isCollapseOpen = (element) => {
    return element.classList.contains('in') || element.classList.contains('show');
};

const showCollapseElement = (element) => {
    if (!element) {
        return;
    }
    element.classList.add('in', 'show');
    element.style.height = `${element.scrollHeight}px`;
    requestAnimationFrame(() => {
        element.style.height = 'auto';
    });
    dispatchCustomEvent(element, 'shown.bs.collapse');
};

const hideCollapseElement = (element) => {
    if (!element) {
        return;
    }
    element.classList.remove('in', 'show');
    element.style.height = '0px';
    dispatchCustomEvent(element, 'hidden.bs.collapse');
};

const toggleCollapseElement = (element) => {
    if (!element) {
        return;
    }
    if (isCollapseOpen(element)) {
        hideCollapseElement(element);
    } else {
        showCollapseElement(element);
    }
};

const activateTabTrigger = (trigger) => {
    if (!trigger) {
        return;
    }
    const target = findTargetElement(trigger);
    if (!target) {
        return;
    }
    const parentNav = trigger.closest('ul');
    if (parentNav) {
        parentNav.querySelectorAll('.active').forEach((active) => active.classList.remove('active'));
    }
    const li = trigger.closest('li');
    if (li) {
        li.classList.add('active');
    }
    const container = target.parentElement;
    if (container) {
        container.querySelectorAll('.tab-pane').forEach((pane) => {
            pane.classList.remove('active', 'in');
            pane.style.display = 'none';
        });
    }
    target.classList.add('active', 'in');
    target.style.display = 'block';
    dispatchCustomEvent(target, 'shown.bs.tab');
};

let tooltipElement = null;
const tooltipState = {
    activeTrigger: null
};

const ensureTooltipElement = () => {
    if (!tooltipElement) {
        tooltipElement = document.createElement('div');
        tooltipElement.className = 'voyager-tooltip';
        document.body.appendChild(tooltipElement);
    }
    return tooltipElement;
};

const positionTooltip = (trigger) => {
    const tooltip = ensureTooltipElement();
    const text = trigger.getAttribute('title') || trigger.getAttribute('data-original-title');
    if (!text) {
        return;
    }
    tooltip.textContent = text;
    const rect = trigger.getBoundingClientRect();
    const top = window.scrollY + rect.top - 8;
    const left = window.scrollX + rect.left + rect.width / 2;
    tooltip.style.top = `${top}px`;
    tooltip.style.left = `${left}px`;
    tooltip.classList.add('visible');
    tooltipState.activeTrigger = trigger;
};

const hideTooltip = () => {
    if (tooltipElement) {
        tooltipElement.classList.remove('visible');
    }
    tooltipState.activeTrigger = null;
};

const initTooltips = (scope) => {
    const elements = scope
        ? (Array.isArray(scope) ? scope : [scope])
        : document.querySelectorAll('[data-toggle="tooltip"]');
    elements.forEach((element) => {
        if (element && !element.getAttribute('title') && element.getAttribute('data-original-title')) {
            element.setAttribute('title', element.getAttribute('data-original-title'));
        }
    });
};

const initBootstrapCompat = () => {
    document.addEventListener('click', (event) => {
        const baseTarget = getSafeEventTarget(event);
        const modalTrigger = baseTarget && baseTarget.closest('[data-toggle="modal"]');
        if (modalTrigger) {
            event.preventDefault();
            const modal = findTargetElement(modalTrigger);
            if (modal) {
                showModalElement(modal);
            }
            return;
        }

        const dismissTrigger = baseTarget && baseTarget.closest('[data-dismiss="modal"]');
        if (dismissTrigger) {
            event.preventDefault();
            const modal = dismissTrigger.closest('.modal');
            if (modal) {
                hideModalElement(modal);
            }
            return;
        }

        const dropdownTrigger = baseTarget && baseTarget.closest('[data-toggle="dropdown"], .dropdown-toggle');
        if (dropdownTrigger) {
            event.preventDefault();
            toggleDropdownFromTrigger(dropdownTrigger);
            return;
        }

        const collapseTrigger = baseTarget && baseTarget.closest('[data-toggle="collapse"]');
        if (collapseTrigger) {
            event.preventDefault();
            const target = findTargetElement(collapseTrigger);
            if (target) {
                const parentSelector = collapseTrigger.getAttribute('data-parent');
                if (parentSelector) {
                    const parent = document.querySelector(parentSelector);
                    if (parent) {
                        parent.querySelectorAll('.collapse.show, .collapse.in').forEach((el) => {
                            if (el !== target) {
                                hideCollapseElement(el);
                            }
                        });
                    }
                }
                toggleCollapseElement(target);
                collapseTrigger.setAttribute('aria-expanded', isCollapseOpen(target) ? 'true' : 'false');
            }
            return;
        }

        const tabTrigger = baseTarget && baseTarget.closest('[data-toggle="tab"]');
        if (tabTrigger) {
            event.preventDefault();
            activateTabTrigger(tabTrigger);
            return;
        }

        const buttonToggle = baseTarget && baseTarget.closest('[data-toggle="buttons"] label');
        if (buttonToggle) {
            const input = buttonToggle.querySelector('input');
            const group = buttonToggle.closest('[data-toggle="buttons"]');
            if (input && group) {
                if (input.type === 'radio') {
                    group.querySelectorAll('label').forEach((label) => label.classList.remove('active'));
                    buttonToggle.classList.add('active');
                    input.checked = true;
                } else {
                    buttonToggle.classList.toggle('active');
                    input.checked = buttonToggle.classList.contains('active');
                }
            }
        }
    });

    document.addEventListener('click', (event) => {
        const modal = currentModal();
        const baseTarget = getSafeEventTarget(event);
        if (modal && baseTarget === modal) {
            hideModalElement(modal);
        }
        if (dropdownState.openDropdown && (!baseTarget || !baseTarget.closest('.dropdown'))) {
            closeDropdownMenu(dropdownState.openDropdown);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const modal = currentModal();
            if (modal) {
                hideModalElement(modal);
                return;
            }
            if (dropdownState.openDropdown) {
                closeDropdownMenu(dropdownState.openDropdown);
            }
        }
    });

    document.addEventListener('mouseenter', (event) => {
        const baseTarget = getSafeEventTarget(event);
        const trigger = baseTarget && baseTarget.closest('[data-toggle="tooltip"]');
        if (!trigger) {
            return;
        }
        positionTooltip(trigger);
    }, true);

    document.addEventListener('mouseleave', (event) => {
        const baseTarget = getSafeEventTarget(event);
        const trigger = baseTarget && baseTarget.closest('[data-toggle="tooltip"]');
        if (trigger) {
            hideTooltip();
        }
    }, true);

    initTooltips();
    document.querySelectorAll('.tab-pane').forEach((pane) => {
        if (pane.classList.contains('active')) {
            pane.style.display = 'block';
        } else {
            pane.style.display = 'none';
        }
    });
};

const registerjQueryBridges = () => {
    if (!window.jQuery) {
        return;
    }
    const $ = window.jQuery;
    $.fn.modal = function (action) {
        return this.each(function () {
            if (action === 'hide') {
                hideModalElement(this);
            } else {
                showModalElement(this);
            }
        });
    };
    $.fn.collapse = function (action) {
        return this.each(function () {
            if (action === 'hide') {
                hideCollapseElement(this);
            } else if (action === 'show') {
                showCollapseElement(this);
            } else {
                toggleCollapseElement(this);
            }
        });
    };
    $.fn.tab = function () {
        return this.each(function () {
            activateTabTrigger(this);
        });
    };
    $.fn.dropdown = function () {
        return this.each(function () {
            toggleDropdownFromTrigger(this);
        });
    };
    $.fn.tooltip = function () {
        initTooltips(this.toArray());
        return this;
    };
};

window.VoyagerInitTooltips = initTooltips;
window.VoyagerBootstrapCompat = {
    init: initBootstrapCompat,
    showModal: showModalElement,
    hideModal: hideModalElement
};

registerjQueryBridges();

// Only non-jQuery dependencies here
import PerfectScrollbar from 'perfect-scrollbar';
import Cropper from 'cropperjs';
window.Cropper = Cropper;
import toastrSource from './vendor/toastr.js?raw';
loadLegacyPlugin(toastrSource, 'toastr.js');
import './slugify';
import './multilingual';
import './voyager_tinymce';
import voyagerTinyMCE from './voyager_tinymce_config';
import { loadVoyagerTinyMCE } from './tinymce-loader';
window.voyagerTinyMCE = voyagerTinyMCE;
window.loadVoyagerTinyMCE = loadVoyagerTinyMCE;
import './voyager_ace_editor';
import * as helpers from './helpers.js';
window.helpers = helpers;

import AdminMenu from './components/admin_menu.vue';
import { createApp } from 'vue';

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
window.createVueApp = function (rootComponent = {}) {
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
window.VueRegisterComponent = function (name, definition) {
    voyagerComponentRegistry[name] = definition;
    voyagerActiveApps.forEach((appInstance) => {
        appInstance.component(name, definition);
    });
};
window.VueMountApp = function (selector, rootComponent = {}) {
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

// Create Vue 3 app for admin menu
if (document.getElementById('adminmenu')) {
    const adminMenuApp = createApp({});
    adminMenuApp.component('admin-menu', AdminMenu);
    adminMenuApp.mount('#adminmenu');
}

$(document).ready(function () {
    var appContainer = $(".app-container"),
        fadedOverlay = $('.fadetoblack'),
        hamburger = $('.hamburger');

    initSimpleTables();
    initToggleSwitches();
    initDatePickers();
    initBootstrapCompat();

    const sideMenuEl = document.querySelector('.side-menu');
    if (sideMenuEl) {
        new PerfectScrollbar(sideMenuEl);
    }

    $('#voyager-loader').fadeOut();

    $(".hamburger, .navbar-expand-toggle").on('click', function () {
        appContainer.toggleClass("expanded");
        $(this).toggleClass('is-active');
        if ($(this).hasClass('is-active')) {
            window.localStorage.setItem('voyager.stickySidebar', true);
        } else {
            window.localStorage.setItem('voyager.stickySidebar', false);
        }
    });

    $('select.select2').select2({ width: '100%' });
    $('select.select2-ajax').each(function () {
        $(this).select2({
            width: '100%',
            tags: $(this).hasClass('taggable'),
            createTag: function (params) {
                var term = $.trim(params.term);

                if (term === '') {
                    return null;
                }

                return {
                    id: term,
                    text: term,
                    newTag: true
                }
            },
            ajax: {
                url: $(this).data('get-items-route'),
                data: function (params) {
                    var query = {
                        search: params.term,
                        type: $(this).data('get-items-field'),
                        method: $(this).data('method'),
                        id: $(this).data('id'),
                        page: params.page || 1
                    }
                    return query;
                }
            }
        });

        $(this).on('select2:select', function (e) {
            var data = e.params.data;
            if (data.id == '') {
                // "None" was selected. Clear all selected options
                $(this).val([]).trigger('change');
            } else {
                $(e.currentTarget).find("option[value='" + data.id + "']").attr('selected', 'selected');
            }
        });

        $(this).on('select2:unselect', function (e) {
            var data = e.params.data;
            $(e.currentTarget).find("option[value='" + data.id + "']").attr('selected', false);
        });

        $(this).on('select2:selecting', function (e) {
            if (!$(this).hasClass('taggable')) {
                return;
            }
            var $el = $(this);
            var route = $el.data('route');
            var label = $el.data('label');
            var errorMessage = $el.data('error-message');
            var newTag = e.params.args.data.newTag;

            if (!newTag) return;

            $el.select2('close');

            $.post(route, {
                [label]: e.params.args.data.text,
                _tagging: true,
            }).done(function (data) {
                var newOption = new Option(e.params.args.data.text, data.data.id, false, true);
                $el.append(newOption).trigger('change');
            }).fail(function (error) {
                toastr.error(errorMessage);
            });

            return false;
        });
    });

    $('.match-height').matchHeight();

    const sideMenuNav = document.querySelector('.side-menu .nav');
    if (sideMenuNav) {
        sideMenuNav.addEventListener('click', (event) => {
            const baseTarget = getSafeEventTarget(event);
            const trigger = baseTarget && baseTarget.closest('.dropdown [data-toggle="collapse"]');
            if (!trigger) {
                return;
            }
            const activeDropdown = trigger.closest('.dropdown');
            if (!activeDropdown) {
                return;
            }
            sideMenuNav.querySelectorAll('.dropdown .collapse').forEach((section) => {
                if (section.closest('.dropdown') !== activeDropdown) {
                    hideCollapseElement(section);
                }
            });
        });
    }

    $(document).on('click', '.panel-heading a.panel-action[data-toggle="panel-collapse"]', function (e) {
        e.preventDefault();
        var $this = $(this);

        // Toggle Collapse
        if (!$this.hasClass('panel-collapsed')) {
            $this.parents('.panel').find('.panel-body').slideUp();
            $this.addClass('panel-collapsed');
            $this.removeClass('voyager-angle-up').addClass('voyager-angle-down');
        } else {
            $this.parents('.panel').find('.panel-body').slideDown();
            $this.removeClass('panel-collapsed');
            $this.removeClass('voyager-angle-down').addClass('voyager-angle-up');
        }
    });

    //Toggle fullscreen
    $(document).on('click', '.panel-heading a.panel-action[data-toggle="panel-fullscreen"]', function (e) {
        e.preventDefault();
        var $this = $(this);
        if (!$this.hasClass('voyager-resize-full')) {
            $this.removeClass('voyager-resize-small').addClass('voyager-resize-full');
        } else {
            $this.removeClass('voyager-resize-full').addClass('voyager-resize-small');
        }
        $this.closest('.panel').toggleClass('is-fullscreen');
    });



    // Save shortcut
    $(document).keydown(function (e) {
        if ((e.metaKey || e.ctrlKey) && e.keyCode == 83) { /*ctrl+s or command+s*/
            $(".btn.save").click();
            e.preventDefault();
            return false;
        }
    });

    /********** MARKDOWN EDITOR (DISABLED) **********/

    $('.easymde').each(function () {
        if (this.dataset.voyagerEasymdeNoticeApplied) {
            return;
        }
        this.dataset.voyagerEasymdeNoticeApplied = 'true';
        const notice = document.createElement('div');
        notice.className = 'alert alert-warning mt-2';
        notice.innerText = 'Markdown editor is temporarily disabled pending rewrite. Please edit the raw markdown text below.';
        if (this.parentNode) {
            this.parentNode.insertBefore(notice, this.nextSibling);
        }
    });

    /********** END MARKDOWN EDITOR **********/

});
