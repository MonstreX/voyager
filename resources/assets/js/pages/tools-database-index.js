import { showModal } from '../core/bootstrap-compat';

let listenersAttached = false;

const getToastr = () => window.toastr || (window.Voyager && window.Voyager.toastr) || null;

const parseJsonConfig = () => {
    if (typeof document === 'undefined') return null;
    const el = document.getElementById('voyager-tools-database-index-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[VoyagerToolsDatabase] Failed to parse config', error);
        return null;
    }
};

const normalizeTableInfoRows = (data) => {
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

const clearTableInfoRows = (tbody) => {
    if (!tbody) return;
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
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

const renderTableInfoRows = (tbody, rows) => {
    if (!tbody) return;
    clearTableInfoRows(tbody);
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

export const initToolsDatabaseIndex = () => {
    if (typeof document === 'undefined') return;
    const config = parseJsonConfig();
    if (!config) return;

    const tableInfoRoot = document.getElementById('table_info');
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
            clearTableInfoRows(tableInfoRows);

            fetch(href, { headers: { Accept: 'application/json' } })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch table info');
                    }
                    return response.json();
                })
                .then((data) => {
                    const rows = normalizeTableInfoRows(data);
                    renderTableInfoRows(tableInfoRows, rows);
                    showModal(tableInfoRoot);
                })
                .catch((error) => {
                    console.error('[VoyagerToolsDatabase] table info fetch failed', error);
                    const toastr = getToastr();
                    toastr && toastr.error(config.i18n && config.i18n.internalError ? config.i18n.internalError : 'Internal error');
                });
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest('td.actions .delete_table');
            if (!button) return;
            if (button.classList.contains('remove-bread-warning')) {
                event.preventDefault();
                event.stopPropagation();
                const toastr = getToastr();
                toastr && toastr.warning(config.i18n && config.i18n.deleteBreadBeforeTable ? config.i18n.deleteBreadBeforeTable : '');
                return;
            }
        });
    }

    // noop
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initToolsDatabaseIndex());
};
