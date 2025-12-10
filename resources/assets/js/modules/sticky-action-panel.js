const PANEL_SELECTOR = '.float-action-panel';

const getDatasetValue = (element, name, fallback = '') => {
    if (!element || !element.dataset) {
        return fallback;
    }
    return element.dataset[name] || fallback;
};

const isAutohideEnabled = (panel) => getDatasetValue(panel, 'autohide') === 'true';

const triggerSubmit = (form, submitButton) => {
    if (!form) {
        return;
    }
    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit(submitButton || undefined);
        return;
    }
    if (submitButton) {
        submitButton.click();
        return;
    }
    form.submit();
};

const attachButtonHandler = (button, url, form, redirectField, submitButton) => {
    if (!button || !form || !redirectField || !url) {
        return;
    }
    button.addEventListener('click', (event) => {
        event.preventDefault();
        redirectField.value = url;
        triggerSubmit(form, submitButton);
    });
};

const attachPrimaryButtonReset = (button, redirectField) => {
    if (!button || !redirectField) {
        return;
    }
    button.addEventListener('click', () => {
        redirectField.value = '';
    });
};

const attachAutohideHandlers = (panel) => {
    if (!panel || !isAutohideEnabled(panel)) {
        return;
    }
    const showPanel = () => {
        panel.style.bottom = '0';
    };
    const hidePanel = () => {
        panel.style.bottom = '-48px';
    };
    panel.addEventListener('mouseenter', showPanel);
    panel.addEventListener('mouseleave', hidePanel);
    hidePanel();
};

const initPanel = (panel) => {
    if (!panel || panel.dataset.voyagerStickyInit === 'true') {
        return;
    }
    const form = panel.closest('form');
    if (!form) {
        return;
    }

    const redirectField = form.querySelector('input[name="redirect_to"]');
    const saveButton = panel.querySelector('.btn.save');
    const saveContinueButton = panel.querySelector('.btn-save-and-continue');
    const saveCreateButton = panel.querySelector('.btn-save-and-create');
    const primarySubmitButton = panel.querySelector('.save') || form.querySelector('.save');

    const currentUrl = form.dataset.url || form.dataset.currentUrl || form.getAttribute('action');
    const createUrl = form.dataset.urlCreate || form.dataset.createUrl || '';

    attachPrimaryButtonReset(primarySubmitButton, redirectField);
    attachButtonHandler(saveContinueButton, currentUrl, form, redirectField, primarySubmitButton);
    attachButtonHandler(saveCreateButton, createUrl, form, redirectField, primarySubmitButton);
    attachAutohideHandlers(panel);

    panel.dataset.voyagerStickyInit = 'true';
};

export const initStickyActionPanels = (container = document) => {
    if (!container) {
        return;
    }
    const panels = container.querySelectorAll
        ? container.querySelectorAll(PANEL_SELECTOR)
        : [];
    panels.forEach(initPanel);
};

export const subscribeToEvents = (eventsBus) => {
    if (!eventsBus || typeof eventsBus.on !== 'function') {
        return;
    }
    eventsBus.on('dom:updated', (target) => {
        if (Array.isArray(target)) {
            target.forEach((node) => initStickyActionPanels(node));
            return;
        }
        initStickyActionPanels(target || document);
    });
};
