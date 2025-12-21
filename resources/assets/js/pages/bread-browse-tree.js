import { getCsrfToken } from '../modules/csrf';
import { getToastr } from '../core/toastr';

let listenersAttached = false;

const getBrowseTreeConfig = () => {
    const configEl = document.getElementById('voyager-browse-tree-config');
    if (!configEl) return null;
    try {
        return JSON.parse(configEl.textContent || '{}');
    } catch (error) {
        console.error('[Voyager] Failed to parse browse-tree config', error);
        return null;
    }
};

const postFormUrlEncoded = (url, params) => {
    const headers = {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
    };

    return fetch(url, {
        method: 'POST',
        body: params,
        headers
    });
};

const initBrowseTreeOnce = (config) => {
    if (config.isModelTranslatable && window.VoyagerInitMultilingual) {
        window.VoyagerInitMultilingual('.side-body');
    }

    const nestableContainer = document.querySelector('.dd');
    if (!nestableContainer || !window.VoyagerInitNestable) return;

    if (nestableContainer.dataset.voyagerNestableInit === '1') return;
    nestableContainer.dataset.voyagerNestableInit = '1';

    window.VoyagerInitNestable(nestableContainer, { handle: '.dd-tree-handle' });

    nestableContainer.addEventListener('voyager.sortable.updated', (event) => {
        const structure = event.detail && event.detail.structure
            ? event.detail.structure
            : (window.VoyagerSerializeNestable ? window.VoyagerSerializeNestable(nestableContainer) : []);

        const params = new URLSearchParams();
        params.append('slug', config.slug || '');
        params.append('order', JSON.stringify(structure));
        params.append('_token', getCsrfToken());

        postFormUrlEncoded(config.treeOrderUrl, params)
            .then((response) => response.json())
            .then((data) => {
                const toastr = getToastr();
                if (data.status === 'success') {
                    toastr && toastr.success(data.message);
                } else {
                    toastr && toastr.error(data.message || 'Error updating order');
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                const toastr = getToastr();
                toastr && toastr.error('Error updating order');
            });
    });
};

export const initBreadBrowseTree = () => {
    const config = getBrowseTreeConfig();
    if (!config || !config.slug || !config.treeOrderUrl) return;

    initBrowseTreeOnce(config);

    if (listenersAttached) return;
    listenersAttached = true;

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!target) return;

        const actionBtn = target.closest('.dd [data-action]');
        if (actionBtn && actionBtn.tagName === 'BUTTON') {
            const action = actionBtn.getAttribute('data-action');
            const li = actionBtn.closest('.dd-item');
            if (!li) return;

            if (action === 'collapse') {
                li.classList.add('dd-collapsed');
                actionBtn.style.display = 'none';
                const expandBtn = li.querySelector('[data-action="expand"]');
                if (expandBtn) expandBtn.style.display = 'block';
            }

            if (action === 'expand') {
                li.classList.remove('dd-collapsed');
                actionBtn.style.display = 'none';
                const collapseBtn = li.querySelector('[data-action="collapse"]');
                if (collapseBtn) collapseBtn.style.display = 'block';
            }
            return;
        }

        const statusToggle = target.closest('.voyager-status-toggle');
        if (statusToggle && config.updateFieldUrlTemplate) {
            const id = statusToggle.dataset.id;
            const field = statusToggle.dataset.field;
            const slug = statusToggle.dataset.slug;
            const currentValue = parseInt(statusToggle.dataset.value, 10) || 0;
            const newValue = currentValue ? 0 : 1;
            const updateUrl = config.updateFieldUrlTemplate.replace('__id', id);

            const params = new URLSearchParams();
            params.append('field', field || '');
            params.append('value', String(newValue));
            params.append('slug', slug || '');
            params.append('_token', getCsrfToken());

            postFormUrlEncoded(updateUrl, params)
                .then((response) => response.json())
                .then((data) => {
                    const toastr = getToastr();
                    if (data.status === 'success') {
                        toastr && toastr.success(data.message);
                        statusToggle.dataset.value = String(newValue);
                        statusToggle.classList.toggle('active', !!newValue);
                        statusToggle.classList.toggle('inactive', !newValue);
                    } else {
                        toastr && toastr.error(data.message || 'Error updating field');
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    const toastr = getToastr();
                    toastr && toastr.error('Error updating field');
                });
        }
    });
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initBreadBrowseTree());
};

