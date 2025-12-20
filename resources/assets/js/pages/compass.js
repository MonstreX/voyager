let listenersAttached = false;

const toggleCollapse = (head) => {
    if (!head) return;
    const container = head.parentElement;
    if (!container) return;
    const content = container.querySelector('.collapse-content');
    if (!content) return;

    const isExpanded = content.classList.contains('in');
    if (isExpanded) {
        content.classList.remove('in');
        content.style.display = 'none';
        container.querySelectorAll('.voyager-angle-up').forEach((icon) => {
            icon.style.display = 'none';
        });
        container.querySelectorAll('.voyager-angle-down').forEach((icon) => {
            icon.style.display = '';
        });
        return;
    }

    content.classList.add('in');
    content.style.display = '';
    container.querySelectorAll('.voyager-angle-down').forEach((icon) => {
        icon.style.display = 'none';
    });
    container.querySelectorAll('.voyager-angle-up').forEach((icon) => {
        icon.style.display = '';
    });
};

const showCommandForm = (commandEl) => {
    if (!commandEl) return;
    const form = commandEl.querySelector('.cmd_form');
    if (form) {
        form.style.display = 'block';
    }
    commandEl.classList.add('more_args');
    const firstInput = commandEl.querySelector('input[type="text"]');
    if (firstInput) {
        firstInput.focus();
    }
};

const clearCommandOutput = () => {
    document.querySelectorAll('#commands pre').forEach((pre) => {
        pre.style.display = 'none';
    });
};

const toggleLogStack = (displayId) => {
    if (!displayId) return;
    const log = document.getElementById(displayId);
    if (!log) return;
    log.style.display = log.style.display === 'none' ? '' : 'none';
};

export const initCompass = () => {
    const root = document.querySelector('.page-content.compass');
    if (!root) return;

    if (listenersAttached) return;
    listenersAttached = true;

    root.addEventListener('click', (event) => {
        const target = event.target;
        if (!target) return;

        const collapseHead = target.closest('.collapse-head');
        if (collapseHead) {
            event.preventDefault();
            event.stopPropagation();
            toggleCollapse(collapseHead);
            return;
        }

        const glyphInput = target.closest('.glyphs input[type="text"]');
        if (glyphInput) {
            glyphInput.select();
            return;
        }

        const closeOutput = target.closest('.close-output');
        if (closeOutput) {
            event.preventDefault();
            clearCommandOutput();
            return;
        }

        const command = target.closest('.command');
        if (command) {
            const clickedInsideForm = !!target.closest('.cmd_form');
            if (!clickedInsideForm) {
                showCommandForm(command);
            }
            return;
        }

        const expand = target.closest('.table-container .expand');
        if (expand) {
            event.preventDefault();
            toggleLogStack(expand.dataset.display);
            return;
        }

        const row = target.closest('.table-container tr[data-display]');
        if (row) {
            const clickedLink = !!target.closest('a');
            if (clickedLink) return;
            toggleLogStack(row.dataset.display);
        }
    });

    root.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form || !form.classList || !form.classList.contains('cmd_form')) return;
        const container = form.closest('.command');
        if (!container) return;
        const hidden = form.querySelector('#hidden_cmd');
        if (hidden) {
            hidden.value = container.dataset.command || hidden.value || '';
        }
    });

};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initCompass());
};
