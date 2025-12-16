const ensureEditorsLoaded = () => {
    if (window.Voyager && typeof window.Voyager.loadEditors === 'function') {
        return window.Voyager.loadEditors().catch((error) => {
            console.error('[AdvInlineSet] Failed to load editors bundle', error);
            return null;
        });
    }
    return Promise.resolve(null);
};

const initEditorsInContainer = (container) => {
    if (!container) {
        return;
    }

    if (window.Voyager && typeof window.Voyager.emitDomUpdated === 'function') {
        window.Voyager.emitDomUpdated(container);
    } else if (window.Voyager && window.Voyager.events) {
        window.Voyager.events.emit('dom:updated', container);
    }

    ensureEditorsLoaded().then((module) => {
        const editorsApi = (window.Voyager && window.Voyager.editors) ? window.Voyager.editors : module;
        const initJodit = editorsApi && typeof editorsApi.initJodit === 'function' ? editorsApi.initJodit : null;
        const initAceEditors = editorsApi && typeof editorsApi.initAceEditors === 'function' ? editorsApi.initAceEditors : null;

        if (initJodit) {
            container.querySelectorAll('textarea.inlineSetRichTextBox').forEach((textarea) => {
                const minHeightRaw = textarea.getAttribute('data-min-height');
                const minHeight = minHeightRaw ? parseInt(minHeightRaw, 10) : 100;
                const selector = textarea.id ? `#${CSS.escape(textarea.id)}` : null;
                if (selector) {
                    initJodit(selector, {
                        height: Number.isFinite(minHeight) ? minHeight : 100,
                    });
                }
            });
        }

        if (initAceEditors) {
            initAceEditors(container);
        }
    });
};

const refreshRowIds = (listEl) => {
    const rowIdsInput = listEl.querySelector('.adv-inline-set-row-ids');
    if (!rowIdsInput) {
        return;
    }
    const ids = Array.from(listEl.querySelectorAll('.adv-inline-set-item:not(.adv-inline-set-template)'))
        .map((item) => parseInt(item.getAttribute('data-row-id') || '0', 10))
        .filter((id) => Number.isFinite(id) && id > 0);
    rowIdsInput.value = ids.join(',');
};

const getNextRowId = (listEl) => {
    let maxId = 0;
    listEl.querySelectorAll('.adv-inline-set-item:not(.adv-inline-set-template)').forEach((item) => {
        const value = parseInt(item.getAttribute('data-row-id') || '0', 10);
        if (Number.isFinite(value) && value > maxId) {
            maxId = value;
        }
    });
    return maxId + 1;
};

const replacePlaceholderId = (rootEl, placeholder, rowId) => {
    const replaceValue = String(rowId);
    const walker = document.createTreeWalker(rootEl, NodeFilter.SHOW_ELEMENT, null);
    let node = rootEl;

    const replaceAttribute = (element, attributeName) => {
        const value = element.getAttribute(attributeName);
        if (!value || !value.includes(placeholder)) {
            return;
        }
        element.setAttribute(attributeName, value.split(placeholder).join(replaceValue));
    };

    while (node) {
        if (node instanceof Element) {
            replaceAttribute(node, 'id');
            replaceAttribute(node, 'name');
            replaceAttribute(node, 'for');
            replaceAttribute(node, 'data-row-id');
        }
        node = walker.nextNode();
    }
};

const updateMediaIdsInput = (mediaList) => {
    const listEl = mediaList.closest('.adv-inline-set-list');
    if (!listEl) {
        return;
    }
    const rowId = mediaList.getAttribute('data-row-id');
    const inlineKey = mediaList.getAttribute('data-inline-key');
    const fieldName = listEl.getAttribute('data-field');
    if (!rowId || !inlineKey || !fieldName) {
        return;
    }

    const idsInput = listEl.querySelector(
        `.adv-inline-set-media-ids[name="${CSS.escape(`${fieldName}_${inlineKey}_${rowId}_media_ids`)}"]`
    );
    if (!idsInput) {
        return;
    }

    const ids = Array.from(mediaList.querySelectorAll('.adv-inline-set-media-item'))
        .map((item) => parseInt(item.getAttribute('data-media-id') || '0', 10))
        .filter((id) => Number.isFinite(id) && id > 0);
    idsInput.value = ids.join(',');
};

const markMediaDeleted = (mediaItem) => {
    const mediaList = mediaItem.closest('.adv-inline-set-media-list');
    const listEl = mediaItem.closest('.adv-inline-set-list');
    if (!mediaList || !listEl) {
        mediaItem.remove();
        return;
    }
    const rowId = mediaList.getAttribute('data-row-id');
    const inlineKey = mediaList.getAttribute('data-inline-key');
    const fieldName = listEl.getAttribute('data-field');

    const mediaId = parseInt(mediaItem.getAttribute('data-media-id') || '0', 10);
    mediaItem.remove();

    if (!rowId || !inlineKey || !fieldName || !Number.isFinite(mediaId) || mediaId <= 0) {
        updateMediaIdsInput(mediaList);
        return;
    }

    const perRowDeleted = listEl.querySelector(
        `.adv-inline-set-media-deleted-ids[name="${CSS.escape(`${fieldName}_${inlineKey}_${rowId}_media_deleted_ids`)}"]`
    );
    if (perRowDeleted) {
        const existing = perRowDeleted.value ? perRowDeleted.value.split(',').filter(Boolean) : [];
        if (!existing.includes(String(mediaId))) {
            existing.push(String(mediaId));
            perRowDeleted.value = existing.join(',');
        }
    }

    updateMediaIdsInput(mediaList);
};

const markRowMediaDeleted = (rowEl) => {
    const listEl = rowEl.closest('.adv-inline-set-list');
    const wrapper = rowEl.closest('.adv-inline-set-wrapper');
    if (!listEl || !wrapper) {
        return;
    }
    const globalDeleted = wrapper.querySelector('.adv-inline-set-deleted-media');
    if (!globalDeleted) {
        return;
    }
    const ids = rowEl.querySelectorAll('.adv-inline-set-media-item[data-media-id]');
    const existing = globalDeleted.value ? globalDeleted.value.split(',').filter(Boolean) : [];
    ids.forEach((el) => {
        const mediaId = parseInt(el.getAttribute('data-media-id') || '0', 10);
        if (Number.isFinite(mediaId) && mediaId > 0 && !existing.includes(String(mediaId))) {
            existing.push(String(mediaId));
        }
    });
    globalDeleted.value = existing.join(',');
};

const initMediaSortables = (container) => {
    if (!window.Sortable) {
        return;
    }
    container.querySelectorAll('.adv-inline-set-media-list').forEach((mediaList) => {
        if (mediaList.dataset.sortableInitialized === 'true') {
            return;
        }
        mediaList.dataset.sortableInitialized = 'true';
        window.Sortable.create(mediaList, {
            animation: 200,
            sort: true,
            scroll: true,
            onEnd: () => updateMediaIdsInput(mediaList),
        });
        updateMediaIdsInput(mediaList);
    });
};

const initRowSortable = (listEl) => {
    if (!window.Sortable || listEl.dataset.sortableInitialized === 'true') {
        return;
    }
    listEl.dataset.sortableInitialized = 'true';

    window.Sortable.create(listEl, {
        animation: 200,
        sort: true,
        scroll: true,
        scrollSensitivity: 140,
        scrollSpeed: 18,
        bubbleScroll: true,
        forceAutoScrollFallback: true,
        fallbackOnBody: true,
        handle: '.adv-inline-set-handle',
        ghostClass: 'adv-inline-set-ghost',
        dragClass: 'adv-inline-set-drag',
        onSort: () => refreshRowIds(listEl),
        onEnd: (evt) => {
            refreshRowIds(listEl);
            const movedRow = evt && evt.item ? evt.item : null;
            if (movedRow) {
                movedRow.querySelectorAll('textarea.inlineSetRichTextBox').forEach((textarea) => {
                    if (textarea.jodit && typeof textarea.jodit.destruct === 'function') {
                        textarea.jodit.destruct();
                        delete textarea.jodit;
                    }
                });
                initEditorsInContainer(movedRow);
            }
        },
    });
};

const addRow = (wrapper, listEl) => {
    const fieldName = listEl.getAttribute('data-field');
    const template = fieldName ? document.getElementById(`template_${fieldName}`) : null;
    if (!template) {
        return;
    }

    const rowId = getNextRowId(listEl);
    const fragment = template.content.cloneNode(true);
    const rowEl = fragment.querySelector('.adv-inline-set-item');
    if (!rowEl) {
        return;
    }

    rowEl.classList.remove('adv-inline-set-template');
    rowEl.dataset.new = 'true';
    rowEl.dataset.rowId = String(rowId);

    replacePlaceholderId(rowEl, '%id%', rowId);

    listEl.appendChild(rowEl);
    initEditorsInContainer(rowEl);
    initMediaSortables(rowEl);
    refreshRowIds(listEl);

    const many = listEl.getAttribute('data-many') === '1';
    if (!many) {
        const actionsEl = wrapper.querySelector('.adv-inline-set-actions');
        if (actionsEl) {
            actionsEl.style.display = 'none';
        }
    }
};

const removeRow = (wrapper, listEl, rowEl) => {
    markRowMediaDeleted(rowEl);
    rowEl.remove();
    refreshRowIds(listEl);

    const many = listEl.getAttribute('data-many') === '1';
    if (!many) {
        const actionsEl = wrapper.querySelector('.adv-inline-set-actions');
        if (actionsEl) {
            const hasAnyRows = listEl.querySelector('.adv-inline-set-item:not(.adv-inline-set-template)') !== null;
            actionsEl.style.display = hasAnyRows ? 'none' : '';
        }
    }
};

const initInlineSetWrapper = (wrapper) => {
    const listEl = wrapper.querySelector('.adv-inline-set-list');
    if (!listEl) {
        return;
    }

    initRowSortable(listEl);
    initMediaSortables(listEl);
    initEditorsInContainer(wrapper);
    refreshRowIds(listEl);

    wrapper.addEventListener('click', (event) => {
        const addBtn = event.target.closest('.add-inline-set');
        if (addBtn) {
            event.preventDefault();
            addRow(wrapper, listEl);
            return;
        }

        const deleteRowBtn = event.target.closest('.adv-inline-set-delete');
        if (deleteRowBtn) {
            event.preventDefault();
            const rowEl = deleteRowBtn.closest('.adv-inline-set-item');
            if (rowEl) {
                removeRow(wrapper, listEl, rowEl);
            }
            return;
        }

        const deleteMediaBtn = event.target.closest('.adv-inline-set-media-delete');
        if (deleteMediaBtn) {
            event.preventDefault();
            const mediaItem = deleteMediaBtn.closest('.adv-inline-set-media-item');
            if (mediaItem) {
                markMediaDeleted(mediaItem);
            }
        }
    });

    wrapper.addEventListener('change', (event) => {
        const mediaList = event.target.closest('.adv-inline-set-media-list');
        if (mediaList) {
            updateMediaIdsInput(mediaList);
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.adv-inline-set-wrapper').forEach((wrapper) => {
        initInlineSetWrapper(wrapper);
    });
});
