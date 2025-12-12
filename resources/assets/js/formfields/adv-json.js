// Initialize Sortable for adv_json lists
const initAdvJsonSortable = () => {
    if (typeof window.Sortable === 'undefined') {
        return;
    }

    document.querySelectorAll('.adv-json-list').forEach((list) => {
        if (!list.dataset.sortableInitialized) {
            window.Sortable.create(list, {
                animation: 200,
                handle: '.adv-json-drag-handle',
                draggable: '.adv-json-item',
                onEnd: () => {
                    collectFieldsAndMakeJSON(list);
                }
            });
            list.dataset.sortableInitialized = 'true';
        }
    });
};

// Add new row
document.addEventListener('click', (e) => {
    const addBtn = e.target.closest('.add-json');
    if (!addBtn) return;

    e.preventDefault();
    const field = addBtn.dataset.field;
    const jsonList = document.querySelector(`.adv-json-list[data-field="${field}"]`);
    const addForm = addBtn.closest('.adv-json-add-form');

    if (!jsonList || !addForm) return;

    // Clone the form
    const newItem = addForm.cloneNode(true);
    newItem.classList.remove('adv-json-add-form');
    newItem.classList.add('adv-json-item');

    // Remove labels
    newItem.querySelectorAll('label').forEach(label => label.remove());

    // Update button
    const btn = newItem.querySelector('button');
    if (btn) {
        btn.classList.remove('btn-success', 'add-json');
        btn.classList.add('btn-danger', 'remove-json');
        btn.innerHTML = '<i class="voyager-x"></i>';
    }

    // Clear input values and remove IDs
    newItem.querySelectorAll('input').forEach(input => {
        input.value = '';
        input.removeAttribute('id');
    });

    jsonList.appendChild(newItem);
    collectFieldsAndMakeJSON(jsonList);
    initAdvJsonSortable();
});

// Remove row
document.addEventListener('click', (e) => {
    const removeBtn = e.target.closest('.remove-json');
    if (!removeBtn) return;

    e.preventDefault();
    const item = removeBtn.closest('.adv-json-item');
    const field = removeBtn.dataset.field;
    const jsonList = document.querySelector(`.adv-json-list[data-field="${field}"]`);

    if (!item || !jsonList) return;

    item.remove();
    collectFieldsAndMakeJSON(jsonList);
});

// Handle input changes
document.addEventListener('input', (e) => {
    if (!e.target.matches('.adv-json-item input')) return;

    const masterField = e.target.dataset.masterField;
    const jsonList = document.querySelector(`.adv-json-list[data-field="${masterField}"]`);

    if (jsonList) {
        collectFieldsAndMakeJSON(jsonList);
    }
});

// Collect fields and generate JSON
function collectFieldsAndMakeJSON(jsonList) {
    const field = jsonList.dataset.field;
    const hiddenInput = document.getElementById(field);

    if (!hiddenInput) return;

    const fields = {};
    const rows = [];

    jsonList.querySelectorAll('.adv-json-item').forEach((item) => {
        const row = {};

        item.querySelectorAll('input').forEach((input) => {
            const fieldKey = input.dataset.field;
            const fieldTitle = input.dataset.title;

            if (fieldKey && fieldTitle) {
                if (!fields[fieldKey]) {
                    fields[fieldKey] = fieldTitle;
                }
                row[fieldKey] = input.value;
            }
        });

        if (Object.keys(row).length > 0) {
            rows.push(row);
        }
    });

    const data = { fields, rows };
    hiddenInput.value = JSON.stringify(data);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initAdvJsonSortable();
});

// Re-initialize when DOM is updated
if (window.Voyager && window.Voyager.events) {
    window.Voyager.events.on('dom:updated', () => {
        initAdvJsonSortable();
    });
}
