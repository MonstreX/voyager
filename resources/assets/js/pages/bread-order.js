let listenersAttached = false;

const getToastr = () => window.toastr || (window.Voyager && window.Voyager.toastr) || null;

const parseJsonConfig = () => {
    if (typeof document === 'undefined') return null;
    const el = document.getElementById('voyager-bread-order-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[VoyagerBreadOrder] Failed to parse config', error);
        return null;
    }
};

const getCsrfToken = () => {
    const meta = document.head && document.head.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
};

export const initBreadOrder = () => {
    if (typeof document === 'undefined') return;
    const config = parseJsonConfig();
    if (!config || !config.orderUrl) return;

    const breadNestable = document.querySelector('[data-voyager-bread-order-root]') || document.querySelector('.bread-order .dd');
    if (!breadNestable) return;

    if (!listenersAttached) {
        listenersAttached = true;

        if (window.VoyagerInitNestable) {
            window.VoyagerInitNestable(breadNestable, {
                handle: '.dd-tree-handle',
                allowChildren: false,
            });
        }

        breadNestable.addEventListener('voyager.sortable.updated', (event) => {
            const structure =
                event.detail && event.detail.structure
                    ? event.detail.structure
                    : window.VoyagerSerializeNestable
                        ? window.VoyagerSerializeNestable(breadNestable)
                        : [];

            const payload = new URLSearchParams();
            payload.append('order', JSON.stringify(structure));
            payload.append('_token', getCsrfToken());

            fetch(config.orderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    Accept: 'application/json',
                },
                body: payload.toString(),
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`Order update failed with status ${response.status}`);
                    }
                    const toastr = getToastr();
                    toastr && toastr.success(config.i18n?.updatedOrder || '');
                })
                .catch((error) => {
                    console.error('[VoyagerBreadOrder] order update failed', error);
                    const toastr = getToastr();
                    toastr && toastr.error(config.i18n?.internalError || 'Internal error');
                });
        });
    }
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initBreadOrder());
};

