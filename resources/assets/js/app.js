import './modules/csrf';
import '../sass/app.scss';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

import SimpleTable from './modules/simple-table';
import VoyagerToaster from './modules/toaster';
window.VoyagerSimpleTable = SimpleTable;

import Sortable from 'sortablejs';
window.Sortable = Sortable;

const resolveMatchHeightGroups = () => {
    if (typeof document === 'undefined') {
        return [];
    }
    const groups = [];
    const classElements = Array.from(document.querySelectorAll('.match-height'));
    if (classElements.length > 1) {
        groups.push(classElements);
    }
    const grouped = {};
    document.querySelectorAll('[data-match-height], [data-mh]').forEach((element) => {
        const key = element.getAttribute('data-match-height') || element.getAttribute('data-mh');
        if (!key) {
            return;
        }
        if (!grouped[key]) {
            grouped[key] = [];
        }
        grouped[key].push(element);
    });
    Object.keys(grouped).forEach((key) => {
        if (grouped[key].length > 1) {
            groups.push(grouped[key]);
        }
    });
    return groups;
};

const applyMatchHeight = () => {
    resolveMatchHeightGroups().forEach((elements) => {
        let maxHeight = 0;
        elements.forEach((element) => {
            element.style.minHeight = '';
            element.style.height = '';
            const rect = element.getBoundingClientRect();
            if (rect.height > maxHeight) {
                maxHeight = rect.height;
            }
        });
        if (maxHeight <= 0) {
            return;
        }
        elements.forEach((element) => {
            element.style.minHeight = `${maxHeight}px`;
        });
    });
};

let matchHeightScheduled = false;
const initMatchHeight = () => {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }
    const scheduleUpdate = () => {
        if (matchHeightScheduled) {
            return;
        }
        matchHeightScheduled = true;
        const runner = () => {
            matchHeightScheduled = false;
            applyMatchHeight();
        };
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(runner);
        } else {
            setTimeout(runner, 60);
        }
    };
    applyMatchHeight();
    window.addEventListener('resize', scheduleUpdate);
};

window.VoyagerInitMatchHeight = initMatchHeight;

const logNestable = (...args) => {
    if (typeof console === 'undefined' || typeof console.debug !== 'function') {
        return;
    }
    if (typeof window !== 'undefined' && window.VoyagerNestableDebug === false) {
        return;
    }
    console.debug('[VoyagerNestable]', ...args);
};

const voyagerNestableInstances = new WeakMap();
const parseNestableDataValue = (value) => {
    if (value === undefined || value === null) {
        return value;
    }
    if (value === 'true') {
        return true;
    }
    if (value === 'false') {
        return false;
    }
    if (value === 'null') {
        return null;
    }
    if (value !== '' && !Number.isNaN(Number(value))) {
        return Number(value);
    }
    const trimmed = typeof value === 'string' ? value.trim() : value;
    if (trimmed && (trimmed.charAt(0) === '{' || trimmed.charAt(0) === '[')) {
        try {
            return JSON.parse(trimmed);
        } catch (error) {
            return value;
        }
    }
    return value;
};

const findDirectChildList = (element) => {
    if (!element || !element.children) {
        return null;
    }
    for (let i = 0; i < element.children.length; i += 1) {
        const child = element.children[i];
        if (child && child.classList && child.classList.contains('dd-list')) {
            return child;
        }
    }
    return null;
};

const resolveDdContainer = (element) => {
    if (!element) {
        return null;
    }
    if (element.classList && element.classList.contains('dd')) {
        return element;
    }
    let current = element.parentElement;
    while (current) {
        if (current.classList && current.classList.contains('dd')) {
            return current;
        }
        current = current.parentElement;
    }
    return element;
};

const resolveNestableRootList = (element) => {
    if (!element) {
        return null;
    }
    if (element.classList && element.classList.contains('dd-list')) {
        return element;
    }
    if (element.classList && element.classList.contains('dd')) {
        const childList = findDirectChildList(element);
        if (childList) {
            return childList;
        }
    }
    return element.querySelector ? element.querySelector('.dd-list') : null;
};

const updateListPlaceholderState = (list) => {
    if (!list || !(list instanceof Element)) {
        return;
    }
    const hasItems = Array.from(list.children || []).some(
        (child) => child && child.classList && child.classList.contains('dd-item')
    );
    if (hasItems) {
        list.classList.remove('dd-empty');
        list.removeAttribute('data-voyager-placeholder');
        return;
    }
    list.classList.add('dd-empty');
    list.setAttribute('data-voyager-placeholder', 'true');
};

const ensureChildList = (item, state) => {
    if (!item || !(item instanceof Element)) {
        return null;
    }
    let childList = findDirectChildList(item);
    if (!childList && typeof document !== 'undefined') {
        childList = document.createElement('ol');
        childList.className = 'dd-list dd-empty';
        childList.setAttribute('data-voyager-placeholder', 'true');
        item.appendChild(childList);
        logNestable('created placeholder child list', { item, childList });
    }
    if (!childList) {
        return null;
    }
    updateListPlaceholderState(childList);
    if (state) {
        state.pendingLists.add(childList);
    }
    return childList;
};

const refreshNestableLists = (state) => {
    const rootElement = state ? state.rootContainer : null;
    if (!rootElement) {
        return;
    }
    const lists = new Set();
    const rootList = resolveNestableRootList(rootElement);
    if (rootList) {
        lists.add(rootList);
    }
    rootElement.querySelectorAll('.dd-list').forEach((list) => lists.add(list));
    logNestable('refresh lists', { rootElement, listCount: lists.size });
    lists.forEach((list) => {
        updateListPlaceholderState(list);
        state.pendingLists.add(list);
    });
    rootElement.querySelectorAll('.dd-item').forEach((item) => ensureChildList(item, state));
};

const dispatchNestableUpdated = (state) => {
    const container = state ? state.rootContainer : null;
    if (!container || typeof window === 'undefined' || typeof window.CustomEvent === 'undefined') {
        return;
    }
    const structure = window.VoyagerSerializeNestable(container);
    logNestable('dispatch voyager.sortable.updated', {
        container,
        structureLength: structure.length,
        structure
    });
    const updateEvent = new CustomEvent('voyager.sortable.updated', {
        bubbles: true,
        detail: { structure }
    });
    container.dispatchEvent(updateEvent);
};

const initSortableList = (list, state) => {
    if (!list || !state || state.sortables.has(list) || typeof Sortable === 'undefined') {
        return;
    }
    logNestable('create Sortable instance', { list, childCount: list.children ? list.children.length : 0 });
    const sortable = Sortable.create(list, {
        group: state.groupId,
        handle: state.handleSelector,
        draggable: state.draggableSelector,
        animation: state.animation,
        fallbackOnBody: true,
        swapThreshold: 0.65,
        dragClass: 'dd-dragging',
        ghostClass: 'dd-ghost',
        onStart: () => {
            logNestable('drag start', { list });
            if (state.ddContainer) {
                state.ddContainer.classList.add('dd-sorting');
            }
        },
        onEnd: () => {
            logNestable('drag end', { list });
            if (state.ddContainer) {
                state.ddContainer.classList.remove('dd-sorting');
            }
            refreshNestableLists(state);
            state.pendingLists.forEach((pending) => initSortableList(pending, state));
            state.pendingLists.clear();
            dispatchNestableUpdated(state);
        }
    });
    state.sortables.set(list, sortable);
};

const initNestableContainer = (container, options = {}) => {
    if (
        typeof document === 'undefined' ||
        typeof window === 'undefined' ||
        typeof Sortable === 'undefined' ||
        !container
    ) {
        return null;
    }
    if (voyagerNestableInstances.has(container)) {
        const existingState = voyagerNestableInstances.get(container);
        logNestable('re-init existing container', { container });
        refreshNestableLists(existingState);
        existingState.pendingLists.forEach((list) => initSortableList(list, existingState));
        existingState.pendingLists.clear();
        return existingState;
    }
    const ddContainer = resolveDdContainer(container);
    const rootList = resolveNestableRootList(container) || resolveNestableRootList(ddContainer);
    if (!rootList) {
        return null;
    }
    logNestable('init container', { container, rootList });
    const state = {
        container,
        ddContainer,
        rootContainer: container,
        rootList,
        groupId: options.group || `voyager-nestable-${Math.random().toString(36).slice(2)}`,
        handleSelector: options.handle || '.dd-handle',
        draggableSelector: options.draggable || '.dd-item',
        animation: typeof options.animation === 'number' ? options.animation : 150,
        sortables: new Map(),
        pendingLists: new Set()
    };
    voyagerNestableInstances.set(container, state);
    refreshNestableLists(state);
    state.pendingLists.forEach((list) => initSortableList(list, state));
    state.pendingLists.clear();
    return state;
};

const resolveNestableTargets = (target) => {
    if (typeof document === 'undefined') {
        return [];
    }
    let resolved = [];
    if (!target || target === document) {
        resolved = Array.from(document.querySelectorAll('.dd'));
    } else if (typeof target === 'string') {
        resolved = Array.from(document.querySelectorAll(target));
    } else if (target instanceof Element) {
        resolved = [target];
    } else {
        const isNodeList = typeof NodeList !== 'undefined' && target instanceof NodeList;
        if (isNodeList || Array.isArray(target)) {
            resolved = Array.from(target).filter(Boolean);
        }
    }
    logNestable('resolve targets', { target, resolvedCount: resolved.length });
    return resolved;
};

const getNestableDataFromList = (list) => {
    if (!list) {
        return [];
    }
    const items = [];
    Array.from(list.children || []).forEach((child) => {
        if (!child || !child.classList || !child.classList.contains('dd-item')) {
            return;
        }
        const dataset = child.dataset || {};
        const itemData = {};
        Object.keys(dataset).forEach((key) => {
            itemData[key] = parseNestableDataValue(dataset[key]);
        });
        const childList = findDirectChildList(child);
        const children = getNestableDataFromList(childList);
        if (children.length) {
            itemData.children = children;
        }
        items.push(itemData);
    });
    return items;
};

const serializeNestable = (target) => {
    const containers = resolveNestableTargets(target);
    const container = containers.length ? containers[0] : null;
    if (!container) {
        return [];
    }
    const list = resolveNestableRootList(container);
    if (!list) {
        return [];
    }
    const serialized = getNestableDataFromList(list);
    logNestable('serialize', { target, container, list, length: serialized.length });
    return serialized;
};

window.VoyagerSerializeNestable = serializeNestable;
window.VoyagerInitNestable = (target, options = {}) => {
    const containers = resolveNestableTargets(target);
    logNestable('VoyagerInitNestable call', { target, resolvedCount: containers.length, options });
    const instances = containers
        .map((container) => initNestableContainer(container, options))
        .filter(Boolean);
    return instances.length === 1 ? instances[0] : instances;
};

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

const voyagerSelectLang = Object.assign({
    placeholder: 'Select an option',
    searchPlaceholder: 'Search...',
    noResults: 'No results found',
    searching: 'Searching...',
    loadMore: 'Load more',
    addTag: 'Add',
    tagPrompt: 'Press Enter to add'
}, window.VoyagerSelectLang || {});

let voyagerSelectCounter = 0;
const voyagerSelectInstances = new WeakMap();
let voyagerSelectOpenInstance = null;

const closeOpenVoyagerSelect = (exceptInstance) => {
    if (voyagerSelectOpenInstance && voyagerSelectOpenInstance !== exceptInstance) {
        voyagerSelectOpenInstance.closeDropdown();
    }
};

const getSelectTargets = (scope) => {
    if (!scope) {
        return Array.from(document.querySelectorAll('select.select2, select.select2-ajax'));
    }
    if (typeof scope === 'string') {
        return Array.from(document.querySelectorAll(scope));
    }
    if (scope instanceof HTMLSelectElement) {
        return [scope];
    }
    if (scope instanceof Element) {
        return Array.from(scope.querySelectorAll('select.select2, select.select2-ajax'));
    }
    if (scope instanceof NodeList || Array.isArray(scope)) {
        const items = [];
        Array.from(scope).forEach((node) => {
            if (node instanceof HTMLSelectElement) {
                items.push(node);
            } else if (node instanceof Element) {
                items.push(...node.querySelectorAll('select.select2, select.select2-ajax'));
            }
        });
        return items;
    }
    return [];
};

class VoyagerSelect {
    constructor(select) {
        this.select = select;
        this.multiple = !!select.multiple;
        this.ajax = select.classList.contains('select2-ajax');
        this.taggable = select.classList.contains('taggable') || select.dataset.voyagerTaggable === 'true';
        this.ajaxTaggable = this.taggable && !!select.dataset.route;
        this.disableSearch = select.dataset.voyagerDisableSearch === 'true';
        this.placeholder = select.getAttribute('data-placeholder') || select.getAttribute('placeholder') || voyagerSelectLang.placeholder;
        this.searchPlaceholder = select.getAttribute('data-search-placeholder') || voyagerSelectLang.searchPlaceholder;
        this.loading = false;
        this.ajaxRoute = select.dataset.getItemsRoute || '';
        this.ajaxField = select.dataset.getItemsField || '';
        this.ajaxMethod = select.dataset.method || 'add';
        this.ajaxId = select.dataset.id || '';
        this.ajaxPage = 1;
        this.ajaxHasMore = false;
        this.options = [];
        this.filteredOptions = [];
        this.selectedValues = new Set();
        this.selectedMap = new Map();
        this.highlightIndex = -1;
        voyagerSelectCounter += 1;
        this.id = `voyager-select-${voyagerSelectCounter}`;
        this.init();
    }

    init() {
        if (this.select.dataset.voyagerSelectInit === 'true') {
            return;
        }
        this.select.dataset.voyagerSelectInit = 'true';
        this.select.setAttribute('data-voyager-select-id', this.id);
        this.buildOptionsFromSelect();
        this.buildSelectedValues();
        this.buildDom();
        voyagerSelectInstances.set(this.select, this);
        if (this.ajax) {
            this.fetchOptions('');
        } else {
            this.renderOptions();
        }
        this.renderSelection();
    }

    buildOptionsFromSelect() {
        const options = [];
        Array.from(this.select.options).forEach((option) => {
            options.push({
                value: option.value,
                text: option.textContent,
                disabled: option.disabled,
                element: option
            });
        });
        this.options = options;
        this.filteredOptions = options.slice();
    }

    buildSelectedValues() {
        Array.from(this.select.options).forEach((option) => {
            if (option.selected) {
                this.selectedValues.add(option.value);
                this.selectedMap.set(option.value, {
                    value: option.value,
                    text: option.textContent
                });
            }
        });
    }

    buildDom() {
        this.wrapper = document.createElement('span');
        this.wrapper.className = 'voyager-select select2 select2-container select2-container--default';
        this.wrapper.style.width = this.select.style.width || '100%';
        this.wrapper.dataset.select2Id = this.id;

        const selectionWrapper = document.createElement('span');
        selectionWrapper.className = 'selection';

        this.selectionEl = document.createElement('span');
        this.selectionEl.className = this.multiple
            ? 'select2-selection select2-selection--multiple'
            : 'select2-selection select2-selection--single';
        this.selectionEl.setAttribute('role', 'combobox');
        this.selectionEl.tabIndex = this.select.disabled ? -1 : 0;

        if (this.multiple) {
            this.selectionList = document.createElement('ul');
            this.selectionList.className = 'select2-selection__rendered';
            this.selectionEl.appendChild(this.selectionList);
        } else {
            this.selectionRendered = document.createElement('span');
            this.selectionRendered.className = 'select2-selection__rendered';
            this.selectionRendered.setAttribute('role', 'textbox');
            const arrow = document.createElement('span');
            arrow.className = 'select2-selection__arrow';
            arrow.innerHTML = '<b role="presentation"></b>';
            this.selectionEl.append(this.selectionRendered, arrow);
        }

        selectionWrapper.appendChild(this.selectionEl);
        this.wrapper.appendChild(selectionWrapper);
        const dropdownWrapper = document.createElement('span');
        dropdownWrapper.className = 'dropdown-wrapper';
        dropdownWrapper.setAttribute('aria-hidden', 'true');
        this.wrapper.appendChild(dropdownWrapper);

        this.select.style.display = 'none';
        this.select.parentNode.insertBefore(this.wrapper, this.select);
        this.wrapper.appendChild(this.select);

        this.dropdown = document.createElement('span');
        this.dropdown.className = 'select2-dropdown select2-dropdown--below';
        this.dropdown.style.display = 'none';
        this.dropdown.innerHTML = `
            <span class="select2-search select2-search--dropdown">
                <input class="select2-search__field" type="search" placeholder="${this.searchPlaceholder}" autocomplete="off" autocapitalize="none" spellcheck="false">
            </span>
            <span class="select2-results">
                <ul class="select2-results__options" role="tree"></ul>
            </span>
        `;
        document.body.appendChild(this.dropdown);
        this.searchContainer = this.dropdown.querySelector('.select2-search');
        this.searchInput = this.dropdown.querySelector('.select2-search__field');
        this.resultsList = this.dropdown.querySelector('.select2-results__options');
        if (this.disableSearch && this.searchContainer) {
            this.searchContainer.style.display = 'none';
        }

        this.selectionEl.addEventListener('click', (event) => {
            event.preventDefault();
            this.toggleDropdown();
        });

        this.selectionEl.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.toggleDropdown();
            }
        });

        if (this.searchInput) {
            this.searchInput.addEventListener('input', () => {
                const term = this.searchInput.value;
                if (this.ajax) {
                    this.debounceSearch(term);
                } else {
                    this.filterStaticOptions(term);
                }
            });
            this.searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    const highlighted = this.highlightOptionByIndex(this.highlightIndex);
                    if (highlighted && highlighted.dataset && highlighted.dataset.value) {
                        this.toggleValue(highlighted.dataset.value);
                    } else if (this.taggable) {
                        const term = this.searchInput.value.trim();
                        if (term) {
                            this.createTag(term);
                        }
                    }
                }
            });
        }

        this.resultsList.addEventListener('scroll', () => {
            if (!this.ajax || !this.ajaxHasMore || this.loading) {
                return;
            }
            const nearBottom = (this.resultsList.scrollTop + this.resultsList.clientHeight) >= (this.resultsList.scrollHeight - 40);
            if (nearBottom) {
                this.fetchOptions(this.searchInput ? this.searchInput.value : '', true);
            }
        });
    }

    destroy() {
        this.closeDropdown();
        if (this.dropdown && this.dropdown.parentNode) {
            this.dropdown.parentNode.removeChild(this.dropdown);
        }
        if (this.wrapper && this.wrapper.parentNode) {
            this.wrapper.parentNode.insertBefore(this.select, this.wrapper);
            this.wrapper.parentNode.removeChild(this.wrapper);
        }
        this.select.style.display = '';
        delete this.select.dataset.voyagerSelectInit;
        voyagerSelectInstances.delete(this.select);
    }

    contains(node) {
        return (this.wrapper && this.wrapper.contains(node)) || (this.dropdown && this.dropdown.contains(node));
    }

    toggleDropdown() {
        if (this.dropdown.style.display === 'block') {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    openDropdown() {
        if (this.select.disabled) {
            return;
        }
        closeOpenVoyagerSelect(this);
        const rect = this.selectionEl.getBoundingClientRect();
        this.dropdown.style.position = 'absolute';
        this.dropdown.style.width = `${rect.width}px`;
        this.dropdown.style.minWidth = `${rect.width}px`;
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        const dropdownWidth = rect.width;
        let left = rect.left + window.scrollX;
        const maxLeft = window.scrollX + viewportWidth - dropdownWidth;
        if (left > maxLeft) {
            left = Math.max(window.scrollX, maxLeft);
        }
        this.dropdown.style.left = `${left}px`;
        this.dropdown.style.top = `${rect.bottom + window.scrollY}px`;
        this.dropdown.style.display = 'block';
        this.dropdown.classList.remove('select2-dropdown--above');
        this.dropdown.classList.add('select2-dropdown--below');
        this.wrapper.classList.add('select2-container--open');
        voyagerSelectOpenInstance = this;
        this.highlightIndex = -1;
        if (this.searchInput && !this.disableSearch) {
            this.searchInput.focus();
            this.searchInput.select();
        }
        if (this.ajax && !this.loadedOnce) {
            this.fetchOptions('');
        }
    }

    closeDropdown() {
        if (this.dropdown) {
            this.dropdown.style.display = 'none';
        }
        this.wrapper.classList.remove('select2-container--open');
        if (voyagerSelectOpenInstance === this) {
            voyagerSelectOpenInstance = null;
        }
    }

    updateSelectElement(triggerChange = true) {
        const values = Array.from(this.selectedValues);
        if (this.multiple) {
            Array.from(this.select.options).forEach((option) => {
                option.selected = values.includes(option.value);
            });
            values.forEach((value) => {
                if (!Array.from(this.select.options).some((option) => option.value === value)) {
                    const optionData = this.getOptionByValue(value);
                    const optionEl = new Option(optionData ? optionData.text : value, value, true, true);
                    this.select.add(optionEl);
                }
            });
        } else {
            const value = values.length ? values[0] : '';
            if (this.select.value !== value) {
                const exists = Array.from(this.select.options).some((option) => option.value === value);
                if (!exists && value !== '') {
                    const optionData = this.getOptionByValue(value);
                    const optionEl = new Option(optionData ? optionData.text : value, value, true, true);
                    this.select.add(optionEl);
                }
                this.select.value = value;
            }
        }
        if (triggerChange) {
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    getOptionByValue(value) {
        return this.options.find((option) => option.value === value) || this.selectedMap.get(value);
    }

    toggleValue(value, forceState) {
        if (this.select.disabled) {
            return;
        }
        const shouldSelect = typeof forceState === 'boolean' ? forceState : !this.selectedValues.has(value);
        if (this.multiple) {
            if (shouldSelect) {
                this.selectedValues.add(value);
            } else {
                this.selectedValues.delete(value);
            }
        } else {
            this.selectedValues.clear();
            if (shouldSelect && value !== '') {
                this.selectedValues.add(value);
            }
        }
        const optionData = this.getOptionByValue(value);
        if (optionData) {
            this.selectedMap.set(value, {
                value,
                text: optionData.text
            });
        }
        this.updateSelectElement();
        this.renderSelection();
        this.renderOptions();
        if (!this.multiple) {
            this.closeDropdown();
        }
    }

    renderSelection() {
        if (this.multiple) {
            this.selectionList.innerHTML = '';
            if (!this.selectedValues.size) {
                const placeholder = document.createElement('li');
                placeholder.className = 'select2-selection__placeholder';
                placeholder.textContent = this.placeholder;
                this.selectionList.appendChild(placeholder);
                return;
            }
            this.selectedValues.forEach((value) => {
                const choice = document.createElement('li');
                choice.className = 'select2-selection__choice';
                choice.dataset.value = value;
                const removeBtn = document.createElement('span');
                removeBtn.className = 'select2-selection__choice__remove';
                removeBtn.setAttribute('role', 'presentation');
                removeBtn.innerHTML = '&times;';
                removeBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    this.toggleValue(value, false);
                });
                const option = this.getOptionByValue(value);
                choice.append(removeBtn, document.createTextNode(option ? option.text : value));
                this.selectionList.appendChild(choice);
            });
        } else {
            const value = Array.from(this.selectedValues)[0] || '';
            const option = this.getOptionByValue(value);
            this.selectionRendered.textContent = option ? option.text : this.placeholder;
            if (option) {
                this.selectionRendered.classList.remove('select2-selection__placeholder');
            } else {
                this.selectionRendered.classList.add('select2-selection__placeholder');
            }
        }
    }

    renderOptions() {
        if (!this.resultsList) {
            return;
        }
        this.resultsList.innerHTML = '';
        const options = this.ajax ? this.options : this.filteredOptions;
        if (this.loading) {
            const loadingItem = document.createElement('li');
            loadingItem.className = 'select2-results__option';
            loadingItem.textContent = voyagerSelectLang.searching;
            this.resultsList.appendChild(loadingItem);
            return;
        }
        if (!options.length) {
            const emptyItem = document.createElement('li');
            emptyItem.className = 'select2-results__option';
            emptyItem.textContent = voyagerSelectLang.noResults;
            this.resultsList.appendChild(emptyItem);
            return;
        }
        options.forEach((option, index) => {
            const li = document.createElement('li');
            li.className = 'select2-results__option';
            li.dataset.value = option.value;
            li.setAttribute('role', 'treeitem');
            if (option.disabled) {
                li.setAttribute('aria-disabled', 'true');
            }
            if (this.selectedValues.has(option.value)) {
                li.setAttribute('aria-selected', 'true');
            }
            li.textContent = option.text;
            li.addEventListener('mousedown', (event) => {
                event.preventDefault();
                if (!option.disabled) {
                    this.toggleValue(option.value);
                }
            });
            li.addEventListener('mouseenter', () => {
                this.highlightIndex = index;
                this.updateHighlightedOption();
            });
            this.resultsList.appendChild(li);
        });
        if (this.ajax && this.ajaxHasMore) {
            const more = document.createElement('li');
            more.className = 'select2-results__option';
            more.textContent = voyagerSelectLang.loadMore;
            more.addEventListener('mousedown', (event) => {
                event.preventDefault();
                this.fetchOptions(this.searchInput ? this.searchInput.value : '', true);
            });
            this.resultsList.appendChild(more);
        }
        this.updateHighlightedOption();
    }

    highlightOptionByIndex(index) {
        const items = Array.from(this.resultsList.querySelectorAll('.select2-results__option'));
        if (index < 0 || index >= items.length) {
            return null;
        }
        return items[index];
    }

    updateHighlightedOption() {
        const items = Array.from(this.resultsList.querySelectorAll('.select2-results__option'));
        items.forEach((item, idx) => {
            if (idx === this.highlightIndex) {
                item.classList.add('select2-results__option--highlighted');
            } else {
                item.classList.remove('select2-results__option--highlighted');
            }
        });
    }

    filterStaticOptions(term) {
        const lower = term.toLowerCase();
        if (!lower) {
            this.filteredOptions = this.options.slice();
        } else {
            this.filteredOptions = this.options.filter((option) => option.text.toLowerCase().indexOf(lower) !== -1);
        }
        this.renderOptions();
    }

    debounceSearch(term) {
        clearTimeout(this.searchTimer);
        this.searchTimer = setTimeout(() => {
            this.fetchOptions(term);
        }, 200);
    }

    fetchOptions(term = '', append = false) {
        if (!this.ajaxRoute) {
            return;
        }
        this.loading = true;
        this.renderOptions();
        const params = new URLSearchParams();
        params.set('type', this.ajaxField);
        params.set('method', this.ajaxMethod);
        if (this.ajaxId) {
            params.set('id', this.ajaxId);
        }
        params.set('page', append ? this.ajaxPage + 1 : 1);
        if (term) {
            params.set('search', term);
        }
        fetch(`${this.ajaxRoute}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then((response) => response.json())
            .then((json) => {
                const results = Array.isArray(json.results) ? json.results : [];
                const mapped = results.map((item) => ({
                    value: String(item.id !== undefined ? item.id : ''),
                    text: item.text || ''
                }));
                if (append) {
                    this.options = this.options.concat(mapped);
                    this.ajaxPage += 1;
                } else {
                    this.options = mapped;
                    this.ajaxPage = 1;
                }
                this.ajaxHasMore = json.pagination && json.pagination.more;
                this.loadedOnce = true;
                this.loading = false;
                this.renderOptions();
            })
            .catch(() => {
                this.loading = false;
                this.ajaxHasMore = false;
                this.renderOptions();
            });
    }

    createTag(text) {
        if (this.ajaxTaggable) {
            const route = this.select.dataset.route;
            const label = this.select.dataset.label;
            const errorMessage = this.select.dataset.errorMessage || 'Error creating item';
            if (!route || !label) {
                return;
            }
            const payload = new FormData();
            payload.append(label, text);
            payload.append('_tagging', 'true');
            const token = document.head.querySelector('meta[name="csrf-token"]');
            const headers = token ? { 'X-CSRF-TOKEN': token.getAttribute('content') } : {};
            fetch(route, {
                method: 'POST',
                headers,
                body: payload
            })
                .then((response) => response.json())
                .then((json) => {
                    const newId = json && json.data && json.data.id !== undefined ? json.data.id : text;
                    const option = {
                        value: String(newId),
                        text
                    };
                    this.addOption(option);
                    this.toggleValue(option.value, true);
                })
                .catch(() => {
                    if (window.toastr && typeof window.toastr.error === 'function') {
                        window.toastr.error(errorMessage);
                    } else {
                        console.error(errorMessage);
                    }
                });
        } else {
            const option = {
                value: text,
                text
            };
            this.addOption(option);
            this.toggleValue(option.value, true);
        }
    }

    addOption(option) {
        const exists = this.options.find((opt) => opt.value === option.value);
        if (!exists) {
            this.options.push(option);
        } else {
            exists.text = option.text;
        }
        this.selectedMap.set(option.value, option);
        const domExists = Array.from(this.select.options).some((opt) => opt.value === option.value);
        if (!domExists) {
            const optionEl = new Option(option.text, option.value, false, false);
            this.select.add(optionEl);
        }
        this.filteredOptions = this.options.slice();
        this.renderOptions();
    }

    setOptions(options, selectedValue) {
        this.options = options.map((option) => ({
            value: String(option.id !== undefined ? option.id : option.value),
            text: option.text || option.label || option.value || ''
        }));
        this.filteredOptions = this.options.slice();
        this.select.innerHTML = '';
        this.options.forEach((option) => {
            const optionEl = new Option(option.text, option.value, false, false);
            this.select.add(optionEl);
        });
        this.selectedValues.clear();
        if (selectedValue !== undefined) {
            if (Array.isArray(selectedValue)) {
                selectedValue.forEach((value) => this.selectedValues.add(String(value)));
            } else if (selectedValue !== null && selectedValue !== '') {
                this.selectedValues.add(String(selectedValue));
            }
        }
        this.updateSelectElement(false);
        this.renderSelection();
        this.renderOptions();
    }
}

const initVoyagerSelects = (scope) => {
    const elements = getSelectTargets(scope);
    elements.forEach((select) => {
        if (!(select instanceof HTMLSelectElement)) {
            return;
        }
        if (select.dataset.voyagerSelectInit === 'true') {
            return;
        }
        new VoyagerSelect(select);
    });
};

const refreshVoyagerSelect = (select) => {
    if (!select) {
        return;
    }
    const instance = voyagerSelectInstances.get(select);
    if (instance) {
        instance.destroy();
    }
    initVoyagerSelects(select);
};

const setVoyagerSelectOptions = (select, options, selectedValue) => {
    let instance = voyagerSelectInstances.get(select);
    if (!instance) {
        initVoyagerSelects(select);
        instance = voyagerSelectInstances.get(select);
    }
    if (instance) {
        instance.setOptions(options, selectedValue);
    }
};

document.addEventListener('mousedown', (event) => {
    if (voyagerSelectOpenInstance && !voyagerSelectOpenInstance.contains(event.target)) {
        voyagerSelectOpenInstance.closeDropdown();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && voyagerSelectOpenInstance) {
        voyagerSelectOpenInstance.closeDropdown();
    }
});

window.VoyagerInitSelects = initVoyagerSelects;
window.VoyagerSelectRefresh = refreshVoyagerSelect;
window.VoyagerSelectSetOptions = setVoyagerSelectOptions;

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
        if (selector.startsWith('#')) {
            const candidate = document.getElementById(selector.slice(1));
            if (candidate) {
                return candidate;
            }
        }
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

const collapseTransitionDuration = 350;

const runTransition = (element, callback) => {
    let called = false;
    const handler = (event) => {
        if (event && event.target !== element) {
            return;
        }
        called = true;
        element.removeEventListener('transitionend', handler);
        callback();
    };
    element.addEventListener('transitionend', handler);
    setTimeout(() => {
        if (!called) {
            handler({ target: element });
        }
    }, collapseTransitionDuration + 50);
};

const showCollapseElement = (element) => {
    if (!element || element.classList.contains('collapsing') || element.classList.contains('in')) {
        return;
    }
    element.classList.remove('collapse');
    element.style.display = 'block';
    const height = element.scrollHeight;
    element.style.height = '0px';
    element.offsetHeight; // force reflow
    element.classList.add('collapsing');
    element.style.transition = `height ${collapseTransitionDuration}ms ease`;
    requestAnimationFrame(() => {
        element.style.height = `${height}px`;
    });
    runTransition(element, () => {
        element.classList.remove('collapsing');
        element.classList.add('collapse', 'in', 'show');
        element.style.height = 'auto';
        element.style.transition = '';
        dispatchCustomEvent(element, 'shown.bs.collapse');
    });
};

const hideCollapseElement = (element) => {
    if (!element || element.classList.contains('collapsing') || !element.classList.contains('in')) {
        return;
    }
    element.style.height = `${element.scrollHeight}px`;
    element.offsetHeight;
    element.classList.add('collapsing');
    element.classList.remove('collapse', 'in', 'show');
    element.style.transition = `height ${collapseTransitionDuration}ms ease`;
    requestAnimationFrame(() => {
        element.style.height = '0px';
    });
    runTransition(element, () => {
        element.classList.remove('collapsing');
        element.classList.add('collapse');
        element.style.display = 'none';
        element.style.height = '';
        element.style.transition = '';
        dispatchCustomEvent(element, 'hidden.bs.collapse');
    });
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
    let elements = [];
    if (scope) {
        if (Array.isArray(scope)) {
            elements = scope;
        } else if (typeof NodeList !== 'undefined' && scope instanceof NodeList) {
            elements = Array.from(scope);
        } else {
            elements = [scope];
        }
    } else {
        elements = Array.from(document.querySelectorAll('[data-toggle="tooltip"]'));
    }

    elements.forEach((element) => {
        if (element && element instanceof Element && !element.getAttribute('title') && element.getAttribute('data-original-title')) {
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

window.VoyagerInitTooltips = initTooltips;
window.VoyagerBootstrapCompat = {
    init: initBootstrapCompat,
    showModal: showModalElement,
    hideModal: hideModalElement
};

// Only non-jQuery dependencies here
import PerfectScrollbar from 'perfect-scrollbar';
import Cropper from 'cropperjs';
window.Cropper = Cropper;
const voyagerToaster = new VoyagerToaster();
window.toastr = voyagerToaster;

import { initSlugifyFields } from './modules/slugify';
import './multilingual';
import voyagerTinyMCE from './voyager_tinymce_config';
import { loadVoyagerTinyMCE } from './tinymce-loader';
window.voyagerTinyMCE = voyagerTinyMCE;
window.loadVoyagerTinyMCE = loadVoyagerTinyMCE;
import './voyager_ace_editor';
import * as helpers from './helpers.js';
window.helpers = helpers;
window.VoyagerInitSlugify = initSlugifyFields;
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

document.addEventListener('DOMContentLoaded', () => {
    const appContainer = document.querySelector('.app-container');
    const hamburgerButtons = document.querySelectorAll('.hamburger, .navbar-expand-toggle');
    initSimpleTables();
    initToggleSwitches();
    initDatePickers();
    initBootstrapCompat();
    if (window.VoyagerInitSelects) {
        window.VoyagerInitSelects();
    }
    if (window.VoyagerInitSlugify) {
        window.VoyagerInitSlugify('.side-body input[data-slug-origin]');
    }

    const sideMenuEl = document.querySelector('.side-menu');
    if (sideMenuEl) {
        new PerfectScrollbar(sideMenuEl);
    }

    const loader = document.getElementById('voyager-loader');
    if (loader) {
        loader.style.display = 'none';
    }

    hamburgerButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (appContainer) {
                appContainer.classList.toggle('expanded');
            }
            const isActive = button.classList.toggle('is-active');
            if (isActive) {
                window.localStorage.setItem('voyager.stickySidebar', true);
            } else {
                window.localStorage.setItem('voyager.stickySidebar', false);
            }
        });
    });

    if (window.VoyagerInitMatchHeight) {
        window.VoyagerInitMatchHeight();
    }

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

    document.addEventListener('click', (event) => {
        const collapseTrigger = event.target.closest('.panel-heading a.panel-action[data-toggle="panel-collapse"]');
        if (collapseTrigger) {
            event.preventDefault();
            const panel = collapseTrigger.closest('.panel');
            const body = panel ? panel.querySelector('.panel-body') : null;
            if (!panel || !body) {
                return;
            }
            const isCollapsed = collapseTrigger.classList.contains('panel-collapsed');
            if (!isCollapsed) {
                body.style.display = 'none';
                collapseTrigger.classList.add('panel-collapsed');
                collapseTrigger.classList.remove('voyager-angle-up');
                collapseTrigger.classList.add('voyager-angle-down');
            } else {
                body.style.display = '';
                collapseTrigger.classList.remove('panel-collapsed');
                collapseTrigger.classList.remove('voyager-angle-down');
                collapseTrigger.classList.add('voyager-angle-up');
            }
            return;
        }

        const fullscreenTrigger = event.target.closest('.panel-heading a.panel-action[data-toggle="panel-fullscreen"]');
        if (fullscreenTrigger) {
            event.preventDefault();
            fullscreenTrigger.classList.toggle('voyager-resize-full');
            fullscreenTrigger.classList.toggle('voyager-resize-small');
            const panel = fullscreenTrigger.closest('.panel');
            if (panel) {
                panel.classList.toggle('is-fullscreen');
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.keyCode === 83) {
            const saveButtons = document.querySelectorAll('.btn.save');
            saveButtons.forEach((button) => button.click());
            event.preventDefault();
        }
    });

    /********** MARKDOWN EDITOR (DISABLED) **********/

    document.querySelectorAll('.easymde').forEach((textarea) => {
        if (textarea.dataset.voyagerEasymdeNoticeApplied === 'true') {
            return;
        }
        textarea.dataset.voyagerEasymdeNoticeApplied = 'true';
        const notice = document.createElement('div');
        notice.className = 'alert alert-warning mt-2';
        notice.innerText =
            'Markdown editor is temporarily disabled pending rewrite. Please edit the raw markdown text below.';
        if (textarea.parentNode) {
            textarea.parentNode.insertBefore(notice, textarea.nextSibling);
        }
    });

    /********** END MARKDOWN EDITOR **********/

});
