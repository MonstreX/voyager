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
    const el = document.getElementById('voyager-tools-database-index-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[VoyagerToolsDatabase] Failed to parse config', error);
        return null;
    }
};

export const initToolsDatabaseIndex = () => {
    if (typeof document === 'undefined') return;
    const config = parseJsonConfig();
    if (!config) return;

    const tableInfoRoot = document.getElementById('table_info');
    const deleteTableModal = document.getElementById('delete_modal');
    const deleteTableForm = document.getElementById('delete_table_form');
    const deleteTableName = document.getElementById('delete_table_name');
    const deleteBreadModal = document.getElementById('delete_bread_modal');
    const deleteBreadForm = document.getElementById('delete_bread_form');
    const deleteBreadName = document.getElementById('delete_bread_name');

    if (!listenersAttached) {
        listenersAttached = true;

        document.addEventListener('click', (event) => {
            const descLink = event.target.closest('.database-tables .desctable');
            if (!descLink) return;
            event.preventDefault();

            const href = descLink.getAttribute('href');
            if (!href) return;

            const state = window.__voyagerDbTableInfoState;
            if (!state) return;

            state.table.name = descLink.dataset.name || '';
            state.table.rows = [];

            fetch(href, { headers: { Accept: 'application/json' } })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch table info');
                    }
                    return response.json();
                })
                .then((data) => {
                    state.table.rows = Object.keys(data || {}).map((key) => {
                        const val = data[key] || {};
                        return {
                            Field: val.field ?? val.Field,
                            Type: val.type ?? val.Type,
                            Null: val.null ?? val.Null,
                            Key: val.key ?? val.Key,
                            Default: val.default ?? val.Default,
                            Extra: val.extra ?? val.Extra,
                        };
                    });
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
            event.preventDefault();

            const tableName = button.dataset.table || '';
            if (button.classList.contains('remove-bread-warning')) {
                const toastr = getToastr();
                toastr && toastr.warning(config.i18n && config.i18n.deleteBreadBeforeTable ? config.i18n.deleteBreadBeforeTable : '');
                return;
            }

            if (deleteTableName) deleteTableName.textContent = tableName;
            if (deleteTableForm && config.urls && config.urls.deleteTableTemplate) {
                deleteTableForm.action = config.urls.deleteTableTemplate.replace('__database', tableName);
            }
            showModal(deleteTableModal);
        });

        const deleteTableConfirm = document.getElementById('delete_table_confirm');
        if (deleteTableConfirm) {
            deleteTableConfirm.addEventListener('click', () => {
                if (!deleteTableForm) return;
                deleteTableForm.submit();
            });
        }

        document.addEventListener('click', (event) => {
            const button = event.target.closest('table .bread_actions .delete');
            if (!button) return;
            event.preventDefault();

            const id = button.dataset.id || '';
            const name = button.dataset.name || '';
            if (deleteBreadName) deleteBreadName.textContent = name;
            if (deleteBreadForm && config.urls && config.urls.deleteBreadTemplate) {
                deleteBreadForm.action = config.urls.deleteBreadTemplate.replace('__id', id);
            }
            showModal(deleteBreadModal);
        });

        const deleteBreadConfirm = document.getElementById('delete_bread_confirm');
        if (deleteBreadConfirm) {
            deleteBreadConfirm.addEventListener('click', () => {
                if (!deleteBreadForm) return;
                deleteBreadForm.submit();
            });
        }
    }

    if (tableInfoRoot && window.Voyager && typeof window.Voyager.withVue === 'function') {
        if (!window.__voyagerDbTableInfoState) {
            window.__voyagerDbTableInfoState = {
                table: {
                    name: '',
                    rows: []
                }
            };
        }

        if (tableInfoRoot.dataset.voyagerVueMounted !== '1') {
            window.Voyager.withVue(function (Vue) {
                const app = Vue.createApp({
                    data: function () {
                        return {
                            table: window.__voyagerDbTableInfoState.table,
                        };
                    },
                }).mount('#table_info');
                window.__voyagerDbTableInfoState.table = app.table;
                tableInfoRoot.dataset.voyagerVueMounted = '1';
            });
        }
    }
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initToolsDatabaseIndex());
};
