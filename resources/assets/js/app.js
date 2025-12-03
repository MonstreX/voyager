import './modules/csrf';
import '../sass/app.scss';

// Core
import { initBootstrapCompat, showModal, hideModal, initTooltips } from './core/bootstrap-compat';
import { initGlobalEvents } from './core/events';
import { initVueBridge } from './core/vue-bridge';

// Components
import { initDatePickers } from './components/datepicker';
import { initToggleSwitches } from './components/toggle';
import { initVoyagerSelects, refreshVoyagerSelect, setVoyagerSelectOptions } from './components/select';
import { initNestable, serializeNestable } from './components/nestable';
import { initMatchHeight } from './components/match-height';
import { initMarkdownEditor } from './components/editor-markdown';

// Modules
import VoyagerToaster from './modules/toaster';
import { initSlugifyFields } from './modules/slugify';
import SimpleTable, { initSimpleTables } from './modules/simple-table';

// Legacy / Vendor
import './voyager_ace_editor';
import './multilingual';
import voyagerTinyMCE from './voyager_tinymce_config';
import { loadVoyagerTinyMCE } from './tinymce-loader';
import * as helpers from './helpers.js';
import Cropper from 'cropperjs';

// Global Exports
window.VoyagerBootstrapCompat = { init: initBootstrapCompat, showModal, hideModal };
window.VoyagerInitTooltips = initTooltips;
window.VoyagerInitDatePickers = initDatePickers;
window.VoyagerInitToggles = initToggleSwitches;
window.VoyagerInitSelects = initVoyagerSelects;
window.VoyagerSelectRefresh = refreshVoyagerSelect;
window.VoyagerSelectSetOptions = setVoyagerSelectOptions;
window.VoyagerInitMatchHeight = initMatchHeight;
window.VoyagerInitNestable = initNestable;
window.VoyagerSerializeNestable = serializeNestable;
window.VoyagerSimpleTable = SimpleTable;
window.VoyagerInitSimpleTables = initSimpleTables;
window.VoyagerInitSlugify = initSlugifyFields;
window.voyagerTinyMCE = voyagerTinyMCE;
window.loadVoyagerTinyMCE = loadVoyagerTinyMCE;
window.helpers = helpers;
window.Cropper = Cropper;
window.toastr = new VoyagerToaster();

document.addEventListener('DOMContentLoaded', () => {
    // Init Core
    initBootstrapCompat();
    initGlobalEvents();
    initVueBridge();

    // Init Components
    initSimpleTables();
    initToggleSwitches();
    initDatePickers();
    initVoyagerSelects();
    initMatchHeight();
    initMarkdownEditor();

    // Init Modules
    if (window.VoyagerInitSlugify) {
        window.VoyagerInitSlugify('.side-body input[data-slug-origin]');
    }
});
