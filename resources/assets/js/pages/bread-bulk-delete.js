const getToastr = () => window.toastr || (window.Voyager && window.Voyager.toastr);

const getSelectedIds = (tableSelector) => {
    const table = document.querySelector(tableSelector);
    if (!table) return [];

    return Array.from(table.querySelectorAll('input[type="checkbox"]:checked'))
        .filter((checkbox) => !checkbox.classList.contains('select_all'))
        .map((checkbox) => checkbox.value);
};

export const initBreadBulkDelete = () => {
    const bulkDeleteBtn = document.getElementById('bulk_delete_btn');
    if (!bulkDeleteBtn) return;

    if (bulkDeleteBtn.dataset.voyagerBulkDeleteInit === '1') return;
    bulkDeleteBtn.dataset.voyagerBulkDeleteInit = '1';

    const tableSelector = bulkDeleteBtn.dataset.bulkDeleteTable || '#dataTable';
    const nothingMessage = bulkDeleteBtn.dataset.bulkDeleteNothing || '';
    const pluralName = bulkDeleteBtn.dataset.bulkDeletePlural || '';
    const singularName = bulkDeleteBtn.dataset.bulkDeleteSingular || '';

    bulkDeleteBtn.addEventListener('click', (event) => {
        const ids = getSelectedIds(tableSelector);
        const count = ids.length;

        if (!count) {
            event.preventDefault();
            event.stopPropagation();
            const toastr = getToastr();
            toastr && toastr.warning(nothingMessage);
            return;
        }

        const bulkDeleteCount = document.getElementById('bulk_delete_count');
        const bulkDeleteDisplayName = document.getElementById('bulk_delete_display_name');
        const bulkDeleteInput = document.getElementById('bulk_delete_input');

        if (bulkDeleteCount) {
            bulkDeleteCount.textContent = String(count);
        }

        if (bulkDeleteDisplayName) {
            const name = count > 1 ? pluralName : singularName;
            bulkDeleteDisplayName.textContent = (name || '').toLowerCase();
        }

        if (bulkDeleteInput) {
            bulkDeleteInput.value = ids.join(',');
        }
    }, true);
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initBreadBulkDelete());
};

