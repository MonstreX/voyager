let listenersAttached = false;

const parseJsonConfig = () => {
    if (typeof document === 'undefined') return null;
    const el = document.getElementById('voyager-roles-edit-add-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[VoyagerRolesEditAdd] Failed to parse config', error);
        return null;
    }
};

const getGroupListInputs = (groupCheckbox) => {
    const container = groupCheckbox ? groupCheckbox.parentElement : null;
    const list = container ? container.querySelector('ul') : null;
    return list ? Array.from(list.querySelectorAll("input[type='checkbox']")) : [];
};

export const initRolesEditAdd = () => {
    if (typeof document === 'undefined') return;
    const config = parseJsonConfig();
    if (!config) return;

    if (typeof window.VoyagerInitToggles === 'function') {
        window.VoyagerInitToggles();
    }

    if (listenersAttached) {
        return;
    }
    listenersAttached = true;

    const permissionGroups = Array.from(document.querySelectorAll('.permission-group'));

    const syncGroupState = () => {
        permissionGroups.forEach((group) => {
            const inputs = getGroupListInputs(group);
            if (!inputs.length) return;
            group.checked = inputs.every((input) => input.checked);
        });
    };

    permissionGroups.forEach((group) => {
        group.addEventListener('change', () => {
            getGroupListInputs(group).forEach((input) => {
                input.checked = group.checked;
            });
        });
    });

    const setAllPermissions = (checked) => {
        document.querySelectorAll('ul.permissions input[type="checkbox"]').forEach((input) => {
            input.checked = checked;
        });
        syncGroupState();
    };

    document.querySelectorAll('.permission-select-all').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            setAllPermissions(true);
        });
    });

    document.querySelectorAll('.permission-deselect-all').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            setAllPermissions(false);
        });
    });

    document.querySelectorAll('.the-permission').forEach((input) => {
        input.addEventListener('change', syncGroupState);
    });

    syncGroupState();
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initRolesEditAdd());
};

