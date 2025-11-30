import './jquery-first';
import '../sass/app.scss';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

import '../sass/bootstrap/javascripts/bootstrap';
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


    $(".side-menu .nav .dropdown").on('show.bs.collapse', function () {
        return $(".side-menu .nav .dropdown .collapse").collapse('hide');
    });

    $('.panel-collapse').on('hide.bs.collapse', function (e) {
        var target = $(e.target);
        if (!target.is('a')) {
            target = target.parent();
        }
        if (!target.hasClass('collapsed')) {
            return;
        }
        e.stopPropagation();
        e.preventDefault();
    });

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
