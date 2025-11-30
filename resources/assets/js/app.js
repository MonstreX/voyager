import './jquery-first';
import '../sass/app.scss';



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

import bootstrapToggleSource from './vendor/bootstrap-toggle.js?raw';
loadLegacyPlugin(bootstrapToggleSource, 'bootstrap-toggle.js');

import nestableSource from './vendor/jquery.nestable.js?raw';
loadLegacyPlugin(nestableSource, 'jquery.nestable.js');

import matchHeightSource from './vendor/jquery.matchHeight.js?raw';
loadLegacyPlugin(matchHeightSource, 'jquery.matchHeight.js');

import momentSource from './vendor/moment.js?raw';
loadLegacyPlugin(momentSource, 'moment.js');

import bootstrapDatepickerSource from './vendor/bootstrap-datepicker.js?raw';
loadLegacyPlugin(bootstrapDatepickerSource, 'bootstrap-datepicker.js');

import datetimePickerSource from './vendor/datetimepicker.js?raw';
loadLegacyPlugin(datetimePickerSource, 'datetimepicker.js');

import bootstrapDateTimePickerSource from './vendor/bootstrap-datetimepicker.js?raw';
loadLegacyPlugin(bootstrapDateTimePickerSource, 'bootstrap-datetimepicker.js');

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

import dropzoneSource from './vendor/dropzone.js?raw';
loadLegacyPlugin(dropzoneSource, 'dropzone.js');


import chartSource from './vendor/chart.js?raw';
loadLegacyPlugin(chartSource, 'chart.js');

// Only non-jQuery dependencies here
import PerfectScrollbar from 'perfect-scrollbar';
import Cropper from 'cropperjs';
window.Cropper = Cropper;
import toastrSource from './vendor/toastr.js?raw';
loadLegacyPlugin(toastrSource, 'toastr.js');
import easyMdeSource from './vendor/easymde.js?raw';
loadLegacyPlugin(easyMdeSource, 'easymde.js');
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

    /********** MARKDOWN EDITOR **********/

    $('textarea.easymde').each(function () {
        var easymde = new EasyMDE({
            element: this
        });
        easymde.render();
    });

    /********** END MARKDOWN EDITOR **********/

});
