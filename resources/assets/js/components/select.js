const voyagerSelectLang = Object.assign({
    placeholder: 'Select an option',
    searchPlaceholder: 'Search...',
    noResults: 'No results found',
    searching: 'Searching...',
    loadMore: 'Load more',
    addTag: 'Add',
    tagPrompt: 'Press Enter to add'
}, window.VoyagerSelectLang || {});

let voyagerSelectCounter = 0;
const voyagerSelectInstances = new WeakMap();
let voyagerSelectOpenInstance = null;

const closeOpenVoyagerSelect = (exceptInstance) => {
    if (voyagerSelectOpenInstance && voyagerSelectOpenInstance !== exceptInstance) {
        voyagerSelectOpenInstance.closeDropdown();
    }
};

const getSelectTargets = (scope) => {
    if (!scope) {
        return Array.from(document.querySelectorAll('select.select2, select.select2-ajax'));
    }
    if (typeof scope === 'string') {
        return Array.from(document.querySelectorAll(scope));
    }
    if (scope instanceof HTMLSelectElement) {
        return [scope];
    }
    if (scope instanceof Element) {
        return Array.from(scope.querySelectorAll('select.select2, select.select2-ajax'));
    }
    if (scope instanceof NodeList || Array.isArray(scope)) {
        const items = [];
        Array.from(scope).forEach((node) => {
            if (node instanceof HTMLSelectElement) {
                items.push(node);
            } else if (node instanceof Element) {
                items.push(...node.querySelectorAll('select.select2, select.select2-ajax'));
            }
        });
        return items;
    }
    return [];
};

export class VoyagerSelect {
    constructor(select) {
        this.select = select;
        this.multiple = !!select.multiple;
        this.ajax = select.classList.contains('select2-ajax');
        this.taggable = select.classList.contains('taggable') || select.dataset.voyagerTaggable === 'true';
        this.ajaxTaggable = this.taggable && !!select.dataset.route;
        this.disableSearch = select.dataset.voyagerDisableSearch === 'true';
        this.placeholder = select.getAttribute('data-placeholder') || select.getAttribute('placeholder') || voyagerSelectLang.placeholder;
        this.searchPlaceholder = select.getAttribute('data-search-placeholder') || voyagerSelectLang.searchPlaceholder;
        this.loading = false;
        this.ajaxRoute = select.dataset.getItemsRoute || '';
        this.ajaxField = select.dataset.getItemsField || '';
        this.ajaxMethod = select.dataset.method || 'add';
        this.ajaxId = select.dataset.id || '';
        this.ajaxPage = 1;
        this.ajaxHasMore = false;
        this.options = [];
        this.filteredOptions = [];
        this.selectedValues = new Set();
        this.selectedMap = new Map();
        this.highlightIndex = -1;
        voyagerSelectCounter += 1;
        this.id = `voyager-select-${voyagerSelectCounter}`;
        this.init();
    }

    init() {
        if (this.select.dataset.voyagerSelectInit === 'true') {
            return;
        }
        this.select.dataset.voyagerSelectInit = 'true';
        this.select.setAttribute('data-voyager-select-id', this.id);
        this.buildOptionsFromSelect();
        this.buildSelectedValues();
        this.buildDom();
        voyagerSelectInstances.set(this.select, this);
        if (this.ajax) {
            this.fetchOptions('');
        } else {
            this.renderOptions();
            this.adjustWrapperWidth();
        }
        this.renderSelection();
    }

    buildOptionsFromSelect() {
        const options = [];
        Array.from(this.select.options).forEach((option) => {
            options.push({
                value: option.value,
                text: option.textContent,
                disabled: option.disabled,
                element: option
            });
        });
        this.options = options;
        this.filteredOptions = options.slice();
    }

    buildSelectedValues() {
        Array.from(this.select.options).forEach((option) => {
            if (option.selected) {
                this.selectedValues.add(option.value);
                this.selectedMap.set(option.value, {
                    value: option.value,
                    text: option.textContent
                });
            }
        });
    }

    buildDom() {
        this.wrapper = document.createElement('span');
        this.wrapper.className = 'voyager-select-wrapper select2 select2-container select2-container--default';
        this.wrapper.style.width = this.select.style.width || '100%';
        this.wrapper.dataset.select2Id = this.id;

        const selectionWrapper = document.createElement('span');
        selectionWrapper.className = 'selection';

        this.selectionEl = document.createElement('span');
        this.selectionEl.className = this.multiple
            ? 'select2-selection select2-selection--multiple'
            : 'select2-selection select2-selection--single';
        this.selectionEl.setAttribute('role', 'combobox');
        this.selectionEl.tabIndex = this.select.disabled ? -1 : 0;

        if (this.multiple) {
            this.selectionList = document.createElement('ul');
            this.selectionList.className = 'select2-selection__rendered';
            this.selectionEl.appendChild(this.selectionList);
        } else {
            this.selectionRendered = document.createElement('span');
            this.selectionRendered.className = 'select2-selection__rendered';
            this.selectionRendered.setAttribute('role', 'textbox');
            const arrow = document.createElement('span');
            arrow.className = 'select2-selection__arrow';
            arrow.innerHTML = '<b role="presentation"></b>';
            this.selectionEl.append(this.selectionRendered, arrow);
        }

        selectionWrapper.appendChild(this.selectionEl);
        this.wrapper.appendChild(selectionWrapper);
        const dropdownWrapper = document.createElement('span');
        dropdownWrapper.className = 'dropdown-wrapper';
        dropdownWrapper.setAttribute('aria-hidden', 'true');
        this.wrapper.appendChild(dropdownWrapper);

        this.select.style.display = 'none';
        this.select.parentNode.insertBefore(this.wrapper, this.select);
        this.wrapper.appendChild(this.select);

        this.dropdown = document.createElement('span');
        this.dropdown.className = 'select2-dropdown select2-dropdown--below';
        this.dropdown.style.display = 'none';
        this.dropdown.style.zIndex = '10000000'; // Ensure it's above modals
        this.dropdown.innerHTML = `
            <span class="select2-search select2-search--dropdown">
                <input class="select2-search__field" type="search" placeholder="${this.searchPlaceholder}" autocomplete="off" autocapitalize="none" spellcheck="false">
            </span>
            <span class="select2-results">
                <ul class="select2-results__options" role="tree"></ul>
            </span>
        `;
        document.body.appendChild(this.dropdown);
        this.searchContainer = this.dropdown.querySelector('.select2-search');
        this.searchInput = this.dropdown.querySelector('.select2-search__field');
        this.resultsList = this.dropdown.querySelector('.select2-results__options');
        if (this.disableSearch && this.searchContainer) {
            this.searchContainer.style.display = 'none';
        }

        this.selectionEl.addEventListener('click', (event) => {
            event.preventDefault();
            this.toggleDropdown();
        });

        this.selectionEl.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.toggleDropdown();
            }
        });

        if (this.searchInput) {
            this.searchInput.addEventListener('input', () => {
                const term = this.searchInput.value;
                if (this.ajax) {
                    this.debounceSearch(term);
                } else {
                    this.filterStaticOptions(term);
                }
            });
            this.searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    const highlighted = this.highlightOptionByIndex(this.highlightIndex);
                    if (highlighted && highlighted.dataset && highlighted.dataset.value) {
                        this.toggleValue(highlighted.dataset.value);
                    } else if (this.taggable) {
                        const term = this.searchInput.value.trim();
                        if (term) {
                            this.createTag(term);
                        }
                    }
                }
            });
        }

        this.resultsList.addEventListener('scroll', () => {
            if (!this.ajax || !this.ajaxHasMore || this.loading) {
                return;
            }
            const nearBottom = (this.resultsList.scrollTop + this.resultsList.clientHeight) >= (this.resultsList.scrollHeight - 40);
            if (nearBottom) {
                this.fetchOptions(this.searchInput ? this.searchInput.value : '', true);
            }
        });
    }

    destroy() {
        this.closeDropdown();
        if (this.dropdown && this.dropdown.parentNode) {
            this.dropdown.parentNode.removeChild(this.dropdown);
        }
        if (this.wrapper && this.wrapper.parentNode) {
            this.wrapper.parentNode.insertBefore(this.select, this.wrapper);
            this.wrapper.parentNode.removeChild(this.wrapper);
        }
        this.select.style.display = '';
        delete this.select.dataset.voyagerSelectInit;
        voyagerSelectInstances.delete(this.select);
    }

    contains(node) {
        return (this.wrapper && this.wrapper.contains(node)) || (this.dropdown && this.dropdown.contains(node));
    }

    toggleDropdown() {
        if (this.dropdown.style.display === 'block') {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    openDropdown() {
        if (this.select.disabled) {
            return;
        }
        closeOpenVoyagerSelect(this);
        const rect = this.selectionEl.getBoundingClientRect();
        this.dropdown.style.position = 'absolute';
        this.dropdown.style.width = `${rect.width}px`;
        this.dropdown.style.minWidth = `${rect.width}px`;
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        const dropdownWidth = rect.width;
        let left = rect.left + window.scrollX;
        const maxLeft = window.scrollX + viewportWidth - dropdownWidth;
        if (left > maxLeft) {
            left = Math.max(window.scrollX, maxLeft);
        }
        this.dropdown.style.left = `${left}px`;
        this.dropdown.style.top = `${rect.bottom + window.scrollY}px`;
        this.dropdown.style.display = 'block';
        this.dropdown.classList.remove('select2-dropdown--above');
        this.dropdown.classList.add('select2-dropdown--below');
        this.wrapper.classList.add('select2-container--open');
        this.dropdown.style.width = 'auto';
        this.dropdown.style.minWidth = `${rect.width}px`;
        voyagerSelectOpenInstance = this;
        this.highlightIndex = -1;
        if (this.searchInput && !this.disableSearch) {
            this.searchInput.focus();
            this.searchInput.select();
        }
        if (this.ajax && !this.loadedOnce) {
            this.fetchOptions('');
        }
    }

    closeDropdown() {
        if (this.dropdown) {
            this.dropdown.style.display = 'none';
        }
        this.wrapper.classList.remove('select2-container--open');
        if (voyagerSelectOpenInstance === this) {
            voyagerSelectOpenInstance = null;
        }
    }

    updateSelectElement(triggerChange = true) {
        const values = Array.from(this.selectedValues);
        
        // Helper to ensure option exists
        const ensureOption = (value) => {
            const strValue = String(value);
            let option = Array.from(this.select.options).find((opt) => opt.value === strValue);
            if (!option && strValue !== '') {
                const optionData = this.getOptionByValue(strValue);
                option = new Option(optionData ? optionData.text : strValue, strValue);
                this.select.add(option);
            }
            return option;
        };

        if (this.multiple) {
            // Ensure all selected values have options
            values.forEach(ensureOption);
            
            // Update selected state for all options
            Array.from(this.select.options).forEach((option) => {
                const shouldBeSelected = values.includes(option.value);
                option.selected = shouldBeSelected;
                if (shouldBeSelected) {
                    option.setAttribute('selected', 'selected');
                } else {
                    option.removeAttribute('selected');
                }
            });
        } else {
            const value = values.length ? String(values[0]) : '';
            ensureOption(value);
            this.select.value = value;
            
            // Update attributes for single select
            Array.from(this.select.options).forEach((option) => {
                if (option.value === value) {
                    option.setAttribute('selected', 'selected');
                } else {
                    option.removeAttribute('selected');
                }
            });
        }

        if (triggerChange) {
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    getOptionByValue(value) {
        return this.options.find((option) => option.value === value) || this.selectedMap.get(value);
    }

    toggleValue(value, forceState) {
        if (this.select.disabled) {
            return;
        }
        const shouldSelect = typeof forceState === 'boolean' ? forceState : !this.selectedValues.has(value);
        if (this.multiple) {
            if (shouldSelect) {
                this.selectedValues.add(value);
            } else {
                this.selectedValues.delete(value);
            }
        } else {
            this.selectedValues.clear();
            if (shouldSelect && value !== '') {
                this.selectedValues.add(value);
            }
        }
        const optionData = this.getOptionByValue(value);
        if (optionData) {
            this.selectedMap.set(value, {
                value,
                text: optionData.text
            });
        }
        this.updateSelectElement();
        this.renderSelection();
        this.renderOptions();
        if (!this.multiple) {
            this.closeDropdown();
        }
    }

    renderSelection() {
        if (this.multiple) {
            this.selectionList.innerHTML = '';
            if (!this.selectedValues.size) {
                const placeholder = document.createElement('li');
                placeholder.className = 'select2-selection__placeholder';
                placeholder.textContent = this.placeholder;
                this.selectionList.appendChild(placeholder);
                return;
            }
            this.selectedValues.forEach((value) => {
                const choice = document.createElement('li');
                choice.className = 'select2-selection__choice';
                choice.dataset.value = value;
                const removeBtn = document.createElement('span');
                removeBtn.className = 'select2-selection__choice__remove';
                removeBtn.setAttribute('role', 'presentation');
                removeBtn.innerHTML = '&times;';
                removeBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    this.toggleValue(value, false);
                });
                const option = this.getOptionByValue(value);
                choice.append(removeBtn, document.createTextNode(option ? option.text : value));
                this.selectionList.appendChild(choice);
            });
        } else {
            const value = Array.from(this.selectedValues)[0] || '';
            const option = this.getOptionByValue(value);
            this.selectionRendered.textContent = option ? option.text : this.placeholder;
            if (option) {
                this.selectionRendered.classList.remove('select2-selection__placeholder');
            } else {
                this.selectionRendered.classList.add('select2-selection__placeholder');
            }
        }
    }

    renderOptions() {
        if (!this.resultsList) {
            return;
        }
        this.resultsList.innerHTML = '';
        const options = this.ajax ? this.options : this.filteredOptions;
        if (this.loading) {
            const loadingItem = document.createElement('li');
            loadingItem.className = 'select2-results__option';
            loadingItem.textContent = voyagerSelectLang.searching;
            this.resultsList.appendChild(loadingItem);
            return;
        }
        if (!options.length) {
            const emptyItem = document.createElement('li');
            emptyItem.className = 'select2-results__option';
            emptyItem.textContent = voyagerSelectLang.noResults;
            this.resultsList.appendChild(emptyItem);
            return;
        }
        options.forEach((option, index) => {
            const li = document.createElement('li');
            li.className = 'select2-results__option';
            li.dataset.value = option.value;
            li.setAttribute('role', 'treeitem');
            if (option.disabled) {
                li.setAttribute('aria-disabled', 'true');
            }
            if (this.selectedValues.has(option.value)) {
                li.setAttribute('aria-selected', 'true');
            }
            li.textContent = option.text;
            li.addEventListener('mousedown', (event) => {
                event.preventDefault();
                if (!option.disabled) {
                    this.toggleValue(option.value);
                }
            });
            li.addEventListener('mouseenter', () => {
                this.highlightIndex = index;
                this.updateHighlightedOption();
            });
            this.resultsList.appendChild(li);
        });
        if (this.ajax && this.ajaxHasMore) {
            const more = document.createElement('li');
            more.className = 'select2-results__option';
            more.textContent = voyagerSelectLang.loadMore;
            more.addEventListener('mousedown', (event) => {
                event.preventDefault();
                this.fetchOptions(this.searchInput ? this.searchInput.value : '', true);
            });
            this.resultsList.appendChild(more);
        }
        this.updateHighlightedOption();
    }

    highlightOptionByIndex(index) {
        const items = Array.from(this.resultsList.querySelectorAll('.select2-results__option'));
        if (index < 0 || index >= items.length) {
            return null;
        }
        return items[index];
    }

    updateHighlightedOption() {
        const items = Array.from(this.resultsList.querySelectorAll('.select2-results__option'));
        items.forEach((item, idx) => {
            if (idx === this.highlightIndex) {
                item.classList.add('select2-results__option--highlighted');
            } else {
                item.classList.remove('select2-results__option--highlighted');
            }
        });
    }

    adjustWrapperWidth() {
        if (this.ajax) {
            return;
        }
        const testEl = document.createElement('span');
        testEl.style.visibility = 'hidden';
        testEl.style.position = 'absolute';
        testEl.style.whiteSpace = 'nowrap';
        testEl.className = 'select2-results__option';
        document.body.appendChild(testEl);

        let maxWidth = 0;
        this.options.forEach((option) => {
            testEl.textContent = option.text;
            const width = testEl.getBoundingClientRect().width;
            if (width > maxWidth) {
                maxWidth = width;
            }
        });

        document.body.removeChild(testEl);

        if (maxWidth > 0) {
            const finalWidth = Math.min(maxWidth + 20, 400);
            this.wrapper.style.minWidth = `${finalWidth}px`;
        }
    }

    filterStaticOptions(term) {
        const lower = term.toLowerCase();
        if (!lower) {
            this.filteredOptions = this.options.slice();
        } else {
            this.filteredOptions = this.options.filter((option) => option.text.toLowerCase().indexOf(lower) !== -1);
        }
        this.renderOptions();
    }

    debounceSearch(term) {
        clearTimeout(this.searchTimer);
        this.searchTimer = setTimeout(() => {
            this.fetchOptions(term);
        }, 200);
    }

    fetchOptions(term = '', append = false) {
        if (!this.ajaxRoute) {
            return;
        }
        this.loading = true;
        this.renderOptions();
        const params = new URLSearchParams();
        params.set('type', this.ajaxField);
        params.set('method', this.ajaxMethod);
        if (this.ajaxId) {
            params.set('id', this.ajaxId);
        }
        params.set('page', append ? this.ajaxPage + 1 : 1);
        if (term) {
            params.set('search', term);
        }
        fetch(`${this.ajaxRoute}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then((response) => response.json())
            .then((json) => {
                const results = Array.isArray(json.results) ? json.results : [];
                const mapped = results.map((item) => ({
                    value: String(item.id !== undefined ? item.id : ''),
                    text: item.text || ''
                }));
                if (append) {
                    this.options = this.options.concat(mapped);
                    this.ajaxPage += 1;
                } else {
                    this.options = mapped;
                    this.ajaxPage = 1;
                }
                this.ajaxHasMore = json.pagination && json.pagination.more;
                this.loadedOnce = true;
                this.loading = false;
                this.renderOptions();
            })
            .catch(() => {
                this.loading = false;
                this.ajaxHasMore = false;
                this.renderOptions();
            });
    }

    createTag(text) {
        if (this.ajaxTaggable) {
            const route = this.select.dataset.route;
            const label = this.select.dataset.label;
            const errorMessage = this.select.dataset.errorMessage || 'Error creating item';
            if (!route || !label) {
                return;
            }
            const payload = new FormData();
            payload.append(label, text);
            payload.append('_tagging', 'true');
            const token = document.head.querySelector('meta[name="csrf-token"]');
            const headers = token ? { 'X-CSRF-TOKEN': token.getAttribute('content') } : {};
            fetch(route, {
                method: 'POST',
                headers,
                body: payload
            })
                .then((response) => response.json())
                .then((json) => {
                    const newId = json && json.data && json.data.id !== undefined ? json.data.id : text;
                    const option = {
                        value: String(newId),
                        text
                    };
                    this.addOption(option);
                    this.toggleValue(option.value, true);
                })
                .catch(() => {
                    if (window.toastr && typeof window.toastr.error === 'function') {
                        window.toastr.error(errorMessage);
                    } else {
                        console.error(errorMessage);
                    }
                });
        } else {
            const option = {
                value: text,
                text
            };
            this.addOption(option);
            this.toggleValue(option.value, true);
        }
    }

    addOption(option) {
        const exists = this.options.find((opt) => opt.value === option.value);
        if (!exists) {
            this.options.push(option);
        } else {
            exists.text = option.text;
        }
        this.selectedMap.set(option.value, option);
        const domExists = Array.from(this.select.options).some((opt) => opt.value === option.value);
        if (!domExists) {
            const optionEl = new Option(option.text, option.value, false, false);
            this.select.add(optionEl);
        }
        this.filteredOptions = this.options.slice();
        this.renderOptions();
    }

    setOptions(options, selectedValue) {
        this.options = options.map((option) => ({
            value: String(option.id !== undefined ? option.id : option.value),
            text: option.text || option.label || option.value || ''
        }));
        this.filteredOptions = this.options.slice();
        this.select.innerHTML = '';
        this.options.forEach((option) => {
            const optionEl = new Option(option.text, option.value, false, false);
            this.select.add(optionEl);
        });
        this.selectedValues.clear();
        if (selectedValue !== undefined) {
            if (Array.isArray(selectedValue)) {
                selectedValue.forEach((value) => this.selectedValues.add(String(value)));
            } else if (selectedValue !== null && selectedValue !== '') {
                this.selectedValues.add(String(selectedValue));
            }
        }
        this.updateSelectElement(false);
        this.renderSelection();
        this.renderOptions();
        this.adjustWrapperWidth();
    }
}

export const initVoyagerSelects = (scope) => {
    const elements = getSelectTargets(scope);
    elements.forEach((select) => {
        if (!(select instanceof HTMLSelectElement)) {
            return;
        }
        if (select.dataset.voyagerSelectInit === 'true') {
            return;
        }
        new VoyagerSelect(select);
    });
};

export const refreshVoyagerSelect = (select) => {
    if (!select) {
        return;
    }
    const instance = voyagerSelectInstances.get(select);
    if (instance) {
        instance.destroy();
    }
    initVoyagerSelects(select);
};

export const setVoyagerSelectOptions = (select, options, selectedValue) => {
    let instance = voyagerSelectInstances.get(select);
    if (!instance) {
        initVoyagerSelects(select);
        instance = voyagerSelectInstances.get(select);
    }
    if (instance) {
        instance.setOptions(options, selectedValue);
    }
};

document.addEventListener('mousedown', (event) => {
    if (voyagerSelectOpenInstance && !voyagerSelectOpenInstance.contains(event.target)) {
        voyagerSelectOpenInstance.closeDropdown();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && voyagerSelectOpenInstance) {
        voyagerSelectOpenInstance.closeDropdown();
    }
});
