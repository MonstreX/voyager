let listenersAttached = false;

const getToastr = () => window.toastr || (window.Voyager && window.Voyager.toastr) || null;
const getBootstrap = () => (window.Voyager && window.Voyager.bootstrap) || window.VoyagerBootstrapCompat || null;

const showModal = (modal) => {
    const bootstrap = getBootstrap();
    if (bootstrap && typeof bootstrap.showModal === 'function') {
        bootstrap.showModal(modal);
        return;
    }
    if (!modal) return;
    modal.classList.add('in');
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
};

const parseJsonConfig = () => {
    if (typeof document === 'undefined') return null;
    const el = document.getElementById('voyager-tools-bread-index-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[VoyagerToolsBread] Failed to parse config', error);
        return null;
    }
};

const normalizeRows = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (typeof data !== 'object') return [];
    const candidates = ['rows', 'data', 'columns'];
    for (const key of candidates) {
        if (Array.isArray(data[key])) {
            return data[key];
        }
    }
    return Object.values(data);
};

const clear = (node) => {
    if (!node) return;
    while (node.firstChild) {
        node.removeChild(node.firstChild);
    }
};

const appendCell = (row, value, { strong } = {}) => {
    const td = document.createElement('td');
    const text = value === null || value === undefined ? '' : String(value);
    if (strong) {
        const bold = document.createElement('strong');
        bold.textContent = text;
        td.appendChild(bold);
    } else {
        td.textContent = text;
    }
    row.appendChild(td);
};

const renderRows = (tbody, rows) => {
    if (!tbody) return;
    clear(tbody);
    (rows || []).forEach((rowData) => {
        const candidate = rowData || {};
        const row = document.createElement('tr');
        appendCell(row, candidate.field ?? candidate.Field, { strong: true });
        appendCell(row, candidate.type ?? candidate.Type);
        appendCell(row, candidate.null ?? candidate.Null);
        appendCell(row, candidate.key ?? candidate.Key);
        appendCell(row, candidate.default ?? candidate.Default);
        appendCell(row, candidate.extra ?? candidate.Extra);
        tbody.appendChild(row);
    });
};

export const initToolsBreadIndex = () => {
    if (typeof document === 'undefined') return;
    const config = parseJsonConfig();
    if (!config) return;

    const tableInfoModal = document.getElementById('table_info');
    const tableInfoTitle = document.getElementById('table_info_title');
    const tableInfoRows = document.getElementById('table_info_rows');

    if (!listenersAttached) {
        listenersAttached = true;

        document.addEventListener('click', (event) => {
            const descLink = event.target.closest('.database-tables .desctable');
            if (!descLink) return;
            event.preventDefault();

            const href = descLink.getAttribute('href');
            if (!href) return;

            if (tableInfoTitle) {
                tableInfoTitle.textContent = descLink.dataset.name || '';
            }
            clear(tableInfoRows);

            fetch(href, { headers: { Accept: 'application/json' } })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch table info');
                    }
                    return response.json();
                })
                .then((data) => {
                    renderRows(tableInfoRows, normalizeRows(data));
                    showModal(tableInfoModal);
                })
                .catch((error) => {
                    console.error('[VoyagerToolsBread] table info fetch failed', error);
                    const toastr = getToastr();
                    toastr && toastr.error(config.i18n && config.i18n.internalError ? config.i18n.internalError : 'Internal error');
                });
        });
    }
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initToolsBreadIndex());
};

