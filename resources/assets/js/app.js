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
import { initSlugifyFields } from './modules/slugify';
import SimpleTable, { initSimpleTables } from './modules/simple-table';

// Legacy / Vendor
import './multilingual';
import * as helpers from './helpers.js';
import Cropper from 'cropperjs';
import Sortable from 'sortablejs';

// Voyager namespace and ready.app Promise already initialized in master.blade.php <head>
// Just get the resolver that was created there
const resolveAppReady = window.__resolveAppReady;

// Event system
window.Voyager.events = voyagerEvents;
window.Voyager.emitDomUpdated = (container = document) => {
    voyagerEvents.emit('dom:updated', container);
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
    slugify: initSlugifyFields
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

    // Init Modules
    if (window.VoyagerInitSlugify) {
        window.VoyagerInitSlugify('.side-body input[data-slug-origin]');
    }

    // Emit dom:updated event for initial page load
    // Components can subscribe to this for dynamic reinitialization
    voyagerEvents.emit('dom:updated', document);
});

// Signal that app bundle is ready
resolveAppReady();
document.dispatchEvent(new CustomEvent('voyager:app-ready'));
