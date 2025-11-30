import './jquery-first';
import '../sass/app.scss';



import 'jquery-ui-dist/jquery-ui';
import 'bootstrap';
import 'select2';
import 'datatables.net';
import 'datatables-bootstrap3-plugin';
import 'bootstrap-toggle';
import 'nestable2';
import 'jquery-match-height';
import * as Dropzone from 'dropzone';
window.Dropzone = Dropzone;


import Chart from 'chart.js';
window.Chart = Chart;

// Only non-jQuery dependencies here
import PerfectScrollbar from 'perfect-scrollbar';
import Cropper from 'cropperjs';
window.Cropper = Cropper;
import toastr from 'toastr';
window.toastr = toastr;
import EasyMDE from 'easymde';
window.EasyMDE = EasyMDE;
import * as brace from 'brace';
import 'brace/mode/json';
import 'brace/theme/github';
import './slugify';
import tinymce from 'tinymce';
window.TinyMCE = window.tinymce = tinymce;
import './multilingual';
import './voyager_tinymce';
import voyagerTinyMCE from './voyager_tinymce_config';
window.voyagerTinyMCE = voyagerTinyMCE;
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

    $('.datatable').DataTable({
        "dom": '<"top"fl<"clear">>rt<"bottom"ip<"clear">>'
    });

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
