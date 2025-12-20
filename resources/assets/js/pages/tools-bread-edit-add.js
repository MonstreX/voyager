let globalListenersAttached = false;
let isDraggingBreadRow = false;

const getToastr = () => window.toastr || (window.Voyager && window.Voyager.toastr) || null;
const getBootstrap = () => (window.Voyager && window.Voyager.bootstrap) || window.VoyagerBootstrapCompat || null;

const showModalById = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;

    const bootstrap = getBootstrap();
    if (bootstrap && typeof bootstrap.showModal === 'function') {
        bootstrap.showModal(modal);
        return;
    }
};

const parseJsonConfig = () => {
    if (typeof document === 'undefined') return null;
    const el = document.getElementById('voyager-tools-bread-edit-add-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[VoyagerToolsBreadEditAdd] Failed to parse config', error);
        return null;
    }
};

const hideValidationAlerts = () => {
    document.querySelectorAll('.validation-error').forEach((alert) => {
        alert.style.display = 'none';
    });
};

const showValidationForTextarea = (textarea, visible) => {
    if (!textarea) return;
    const container = textarea.parentElement;
    const validationError = container ? container.querySelector('.validation-error') : null;
    if (validationError) {
        validationError.style.display = visible ? 'block' : 'none';
    }
};

const updateRowOrders = () => {
    document.querySelectorAll('#bread-items .row_order').forEach((input, index) => {
        input.value = index + 1;
    });
};

const sortBreadItems = () => {
    const container = document.getElementById('bread-items');
    if (!container) return;

    const items = Array.from(container.querySelectorAll('div.row.row-dd'));
    items.sort((a, b) => {
        const aInput = a.querySelector('.row_order');
        const bInput = b.querySelector('.row_order');
        const aValue = parseInt(aInput ? aInput.value : '0', 10);
        const bValue = parseInt(bInput ? bInput.value : '0', 10);
        return aValue > bValue ? 1 : -1;
    });
    items.forEach((item) => container.appendChild(item));
};

const initBreadItemsSortable = () => {
    const container = document.getElementById('bread-items');
    if (!container || typeof window.Sortable === 'undefined') {
        return;
    }

    if (!container._voyagerSortable) {
        container.style.userSelect = 'none';
        container.setAttribute('unselectable', 'on');

        const hiddenEditors = new WeakMap();

        const hideEditorsInItem = (item) => {
            if (!item) return;
            const editors = Array.from(item.querySelectorAll('.ace_editor'));
            if (!editors.length) return;
            editors.forEach((node) => {
                hiddenEditors.set(node, node.style.display || '');
                node.style.display = 'none';
            });
        };

        const restoreEditorsInItem = (item) => {
            if (!item) return;
            item.querySelectorAll('.ace_editor').forEach((node) => {
                const prev = hiddenEditors.get(node);
                node.style.display = prev !== undefined ? prev : '';
            });
        };

        container._voyagerSortable = window.Sortable.create(container, {
            handle: '.handler',
            animation: 150,
            ghostClass: 'bread-sortable-ghost',
            dragClass: 'bread-sortable-drag',
            forceFallback: true,
            fallbackOnBody: true,
            scroll: true,
            scrollSensitivity: 140,
            scrollSpeed: 18,
            bubbleScroll: true,
            forceAutoScrollFallback: true,
            onChoose: ({ item }) => {
                isDraggingBreadRow = true;
                hideEditorsInItem(item);
            },
            onUnchoose: ({ item }) => {
                restoreEditorsInItem(item);
                isDraggingBreadRow = false;
            },
            onEnd: ({ item }) => {
                restoreEditorsInItem(item);
                isDraggingBreadRow = false;
                updateRowOrders();
            },
        });
    }

    sortBreadItems();
    updateRowOrders();
};

const initBreadAceEditors = (config) => {
    if (typeof ace === 'undefined') {
        return;
    }

    window.invalidEditors = Array.isArray(window.invalidEditors) ? window.invalidEditors : [];

    document.querySelectorAll('textarea[data-editor]').forEach((textarea) => {
        if (textarea.dataset.voyagerAceInitialized === 'true') {
            return;
        }
        textarea.dataset.voyagerAceInitialized = 'true';

        const mode = textarea.dataset.editor || 'json';
        const editDiv = document.createElement('div');
        textarea.parentNode.insertBefore(editDiv, textarea);

        const editor = ace.edit(editDiv);
        const session = editor.getSession();
        let isValid = false;
        textarea.style.display = 'none';

        session.on('changeAnnotation', () => {
            isValid = session.getAnnotations().length === 0;
            const textareaId = textarea.id;
            if (!textareaId) {
                return;
            }
            if (!isValid && window.invalidEditors.indexOf(textareaId) === -1) {
                window.invalidEditors.push(textareaId);
            } else if (isValid) {
                window.invalidEditors = window.invalidEditors.filter((id) => id !== textareaId);
            }
        });

        editor.on('blur', () => {
            showValidationForTextarea(textarea, !isValid);
        });

        session.setUseWorker(false);
        editor.setAutoScrollEditorIntoView(true);
        editor.$blockScrolling = Infinity;
        editor.setOption('maxLines', 30);
        editor.setOption('minLines', 4);
        editor.setOption('showLineNumbers', false);
        editor.setTheme('ace/theme/github');
        session.setMode('ace/mode/json');
        if (textarea.value) {
            try {
                session.setValue(JSON.stringify(JSON.parse(textarea.value), null, 4));
            } catch (error) {
                console.warn('[VoyagerToolsBreadEditAdd] Failed to parse textarea JSON', error);
                session.setValue(textarea.value);
            }
        }
        session.setMode(`ace/mode/${mode}`);

        const form = textarea.closest('form');
        if (!form) {
            return;
        }

        if (form.dataset.voyagerAceSubmitHandlerAttached !== 'true') {
            form.dataset.voyagerAceSubmitHandlerAttached = 'true';
            form.addEventListener('submit', (event) => {
                if (window.invalidEditors.length) {
                    event.preventDefault();
                    event.stopPropagation();

                    hideValidationAlerts();
                    window.invalidEditors.forEach((id) => {
                        const invalidTextarea = document.getElementById(id);
                        showValidationForTextarea(invalidTextarea, true);
                    });

                    const toastr = getToastr();
                    toastr &&
                        toastr.error(
                            config?.i18n?.invalidJsonMessage || '',
                            config?.i18n?.validationErrorsTitle || '',
                            { preventDuplicates: true, preventOpenDuplicates: true }
                        );
                }
            });
        }

        form.addEventListener('submit', () => {
            const value = session.getValue();
            if (value) {
                try {
                    textarea.value = JSON.stringify(JSON.parse(value));
                } catch {
                    textarea.value = value;
                }
            } else {
                textarea.value = '';
            }
        });
    });
};

const updateRelationshipDisplayName = (input) => {
    const wrapper = input.closest('.row-dd-relationship');
    const label = wrapper ? wrapper.querySelector('h4 strong') : null;
    if (label) {
        label.textContent = input.value || '';
    }
};

const updateRelationshipTableLabel = (dropdown) => {
    const wrapper = dropdown.closest('.voyager-relationship-details') || dropdown.closest('.modal-body');
    const label = wrapper ? wrapper.querySelector('.label_table_name') : null;
    if (label) {
        const selectedOption = dropdown.options[dropdown.selectedIndex];
        label.textContent = selectedOption ? selectedOption.text : '';
    }
};

const handleRelationshipTypeChange = (select) => {
    const fieldWrapper = select.closest('.voyager-relationship-details') || select.closest('.modal-body');
    if (!fieldWrapper) {
        return;
    }

    fieldWrapper
        .querySelectorAll('.belongsToManyShow, .belongsToShow, .hasOneShow, .hasManyShow')
        .forEach((section) => {
            section.style.display = 'none';
        });

    const target = fieldWrapper.querySelector(`.${select.value}Show`);
    if (target) {
        target.style.display = '';
    }

    const hasOneSelect = fieldWrapper.querySelector('.hasOneShow select');
    const belongsToSelect = fieldWrapper.querySelector('.belongsToShow select');
    if (hasOneSelect) {
        hasOneSelect.disabled = true;
    }
    if (belongsToSelect) {
        belongsToSelect.disabled = false;
    }

    const relationshipField = fieldWrapper.querySelector('.relationshipField');
    const relationshipPivot = fieldWrapper.querySelector('.relationshipPivot');
    const relationshipTaggable = fieldWrapper.querySelector('.relationship_taggable');
    const hasOneMany = fieldWrapper.querySelector('.hasOneMany');
    const belongsTo = fieldWrapper.querySelector('.belongsTo');

    if (select.value === 'belongsTo') {
        if (relationshipField) relationshipField.style.display = '';
        if (relationshipPivot) relationshipPivot.style.display = 'none';
        if (relationshipTaggable) relationshipTaggable.style.display = 'none';
        if (hasOneMany) hasOneMany.classList.remove('flexed');
        if (belongsTo) belongsTo.classList.add('flexed');
    } else if (select.value === 'hasOne' || select.value === 'hasMany') {
        if (relationshipField) relationshipField.style.display = '';
        if (relationshipPivot) relationshipPivot.style.display = 'none';
        if (relationshipTaggable) relationshipTaggable.style.display = 'none';
        if (hasOneMany) hasOneMany.classList.add('flexed');
        if (belongsTo) belongsTo.classList.remove('flexed');
        if (hasOneSelect) hasOneSelect.disabled = false;
        if (belongsToSelect) belongsToSelect.disabled = true;
    } else {
        if (relationshipField) relationshipField.style.display = 'none';
        if (relationshipPivot) relationshipPivot.style.display = 'flex';
        if (relationshipTaggable) relationshipTaggable.style.display = '';
    }
};

const populateRowsFromTable = (dropdown, config) => {
    if (!dropdown || !dropdown.value) {
        return;
    }

    const tbl = dropdown.value;
    const container = dropdown.closest('.voyager-relationship-details') || dropdown.closest('.modal-body');
    if (!container) {
        return;
    }

    const base = config?.urls?.databaseIndex;
    if (!base) {
        return;
    }

    const endpoint = `${base}/${encodeURIComponent(tbl)}`;
    fetch(endpoint, { headers: { Accept: 'application/json' } })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Failed to fetch table columns');
            }
            return response.json();
        })
        .then((data) => {
            const options = [];
            for (const key in data) {
                if (Object.prototype.hasOwnProperty.call(data, key)) {
                    const col = data[key];
                    const value = col && col.field ? col.field : key;
                    options.push({ id: value, text: value });
                }
            }

            container.querySelectorAll('.rowDrop').forEach((select) => {
                const selectedValue = select.dataset.selected || '';
                if (window.VoyagerSelectSetOptions) {
                    window.VoyagerSelectSetOptions(select, options, selectedValue);
                    if (selectedValue) {
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    return;
                }

                select.innerHTML = '';
                options.forEach((option) => {
                    const optionEl = document.createElement('option');
                    optionEl.value = option.id;
                    optionEl.text = option.text;
                    select.appendChild(optionEl);
                });
                select.value = selectedValue || (select.options[0] ? select.options[0].value : '');
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        })
        .catch((error) => {
            console.error('[VoyagerToolsBreadEditAdd] populateRowsFromTable failed', error);
        });
};

const initRelationshipControls = (config) => {
    document.querySelectorAll('.relationship_type').forEach((select) => {
        if (select.dataset.voyagerRelationshipTypeInitialized !== 'true') {
            select.dataset.voyagerRelationshipTypeInitialized = 'true';
            select.addEventListener('change', () => handleRelationshipTypeChange(select));
        }
        handleRelationshipTypeChange(select);
    });

    document.querySelectorAll('.btn-new-relationship').forEach((button) => {
        if (button.dataset.voyagerRelationshipNewInitialized !== 'true') {
            button.dataset.voyagerRelationshipNewInitialized = 'true';
            button.addEventListener('click', (event) => {
                event.preventDefault();
                document.querySelectorAll('#new_relationship_modal .relationship_table').forEach((dropdown) => {
                    dropdown.dispatchEvent(new Event('change', { bubbles: true }));
                });
                showModalById('new_relationship_modal');
            });
        }
    });

    document.querySelectorAll('.relationship_table').forEach((dropdown) => {
        if (dropdown.dataset.voyagerRelationshipTableInitialized !== 'true') {
            dropdown.dataset.voyagerRelationshipTableInitialized = 'true';
            dropdown.addEventListener('change', () => {
                populateRowsFromTable(dropdown, config);
                updateRelationshipTableLabel(dropdown);
            });
        }
        updateRelationshipTableLabel(dropdown);
    });

    document.querySelectorAll('.relationship_display_name').forEach((input) => {
        if (input.dataset.voyagerRelationshipDisplayInitialized !== 'true') {
            input.dataset.voyagerRelationshipDisplayInitialized = 'true';
            input.addEventListener('input', () => updateRelationshipDisplayName(input));
        }
        updateRelationshipDisplayName(input);
    });

    document.querySelectorAll('.voyager-relationship-details-btn').forEach((button) => {
        if (button.dataset.voyagerRelationshipDetailsInitialized !== 'true') {
            button.dataset.voyagerRelationshipDetailsInitialized = 'true';
            button.addEventListener('click', (event) => {
                event.preventDefault();
                button.classList.toggle('open');
                const wrapper = button.parentElement ? button.parentElement.parentElement : null;
                const details = wrapper ? wrapper.querySelector('.voyager-relationship-details') : null;
                if (details) {
                    details.style.display = button.classList.contains('open') ? 'block' : 'none';
                }
                if (button.classList.contains('open')) {
                    const dropdown = wrapper ? wrapper.querySelector('select.relationship_table') : null;
                    if (dropdown) {
                        populateRowsFromTable(dropdown, config);
                    }
                }
            });
        }
    });
};

export const initToolsBreadEditAdd = () => {
    if (typeof document === 'undefined') return;
    if (isDraggingBreadRow) return;
    const config = parseJsonConfig();
    if (!config) return;

    hideValidationAlerts();

    if (config.flags?.isModelTranslatable && typeof window.VoyagerInitMultilingual === 'function') {
        window.VoyagerInitMultilingual(document.querySelectorAll('.side-body'), {
            form: 'form',
            editing: true,
        });
    }

    initBreadItemsSortable();

    if (!globalListenersAttached) {
        globalListenersAttached = true;

        if (window.Voyager && typeof window.Voyager.loadEditors === 'function') {
            window.Voyager.loadEditors()
                .then(() => initBreadAceEditors(config))
                .catch((error) => {
                    console.error('[VoyagerToolsBreadEditAdd] Failed to initialize ACE editors', error);
                });
        }

        initRelationshipControls(config);

        if (typeof window.VoyagerInitToggles === 'function') {
            window.VoyagerInitToggles();
        }
        if (typeof window.VoyagerInitTooltips === 'function') {
            window.VoyagerInitTooltips(document.querySelectorAll('[data-toggle="tooltip"]'));
        }
    } else {
        initRelationshipControls(config);
    }
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initToolsBreadEditAdd());
};
