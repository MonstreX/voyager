import Sortable from 'sortablejs';

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
    const structure = serializeNestable(container);
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

export const serializeNestable = (target) => {
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

export const initNestable = (target, options = {}) => {
    const containers = resolveNestableTargets(target);
    logNestable('VoyagerInitNestable call', { target, resolvedCount: containers.length, options });
    const instances = containers
        .map((container) => initNestableContainer(container, options))
        .filter(Boolean);
    return instances.length === 1 ? instances[0] : instances;
};
