const pluginName = 'multilingual';

const defaults = {
    editing: false,
    form: '.form-edit-add',
    transInputs: 'input[data-i18n="true"]',
    langSelectors: '.language-selector input',
};

const instanceRegistry = new WeakMap();

const hasDocument = typeof document !== 'undefined';
const ELEMENT_NODE = typeof Node !== 'undefined' ? Node.ELEMENT_NODE : 1;

const toArray = (collection = []) => Array.prototype.slice.call(collection);

const isElement = (value) => typeof Element !== 'undefined' && value instanceof Element;
const isNodeList = (value) => typeof NodeList !== 'undefined' && value instanceof NodeList;
const isHTMLCollection = (value) => typeof HTMLCollection !== 'undefined' && value instanceof HTMLCollection;

const normalizeTargets = (target) => {
    if (!hasDocument || !target) {
        return [];
    }

    if (typeof target === 'string') {
        return toArray(document.querySelectorAll(target));
    }

    if (isElement(target)) {
        return [target];
    }

    if (isNodeList(target) || isHTMLCollection(target)) {
        return toArray(target);
    }

    if (Array.isArray(target)) {
        return target.filter((node) => isElement(node));
    }

    return [];
};

const queryAll = (root, selector) => {
    if (!selector || !hasDocument) {
        return [];
    }

    if (root && isElement(root)) {
        const scoped = root.querySelectorAll(selector);
        if (scoped.length) {
            return toArray(scoped);
        }
    }

    return toArray(document.querySelectorAll(selector));
};

const findNextElement = (node, selector) => {
    if (!isElement(node)) {
        return null;
    }

    let sibling = node.nextSibling;
    while (sibling) {
        if (sibling.nodeType === ELEMENT_NODE) {
            if (!selector || sibling.matches(selector)) {
                return sibling;
            }
        }
        sibling = sibling.nextSibling;
    }

    return null;
};

const findCodeMirrorInstance = (element) => {
    if (!element || typeof element !== 'object') {
        return null;
    }

    let sibling = element.nextSibling;
    while (sibling) {
        if (sibling.nodeType === ELEMENT_NODE && sibling.classList.contains('CodeMirror') && sibling.CodeMirror) {
            return sibling.CodeMirror;
        }
        sibling = sibling.nextSibling;
    }

    return null;
};

class VoyagerMultilingual {
    constructor(element, options = {}) {
        if (!element) {
            throw new Error('VoyagerMultilingual requires a target element');
        }

        this.element = element;
        this.settings = Object.assign({}, defaults, options);
        this.translationCache = new WeakMap();
        this.userInputMap = new WeakMap();
        this.hiddenInputMap = new WeakMap();

        if (!hasDocument) {
            this.transInputs = [];
            this.langSelectors = [];
            return;
        }

        this.form = this.element.querySelector(this.settings.form) || null;
        this.transInputs = toArray(document.querySelectorAll(this.settings.transInputs));
        this.langSelectors = queryAll(this.element, this.settings.langSelectors);

        if (this.transInputs.length === 0 || this.langSelectors.length === 0) {
            return;
        }

        this.setup();
        this.refresh();
    }

    setup() {
        this.locale = this.returnLocale();
        this.updateLanguageLabels();

        this.langSelectors.forEach((selector) => {
            selector.addEventListener('change', (event) => this.selectLanguage(event));
        });

        if (this.settings.editing && this.form) {
            this.form.addEventListener('submit', () => {
                this.prepareData();
            });
        }
    }

    refresh() {
        this.transInputs.forEach((hiddenInput) => {
            const targetInput = this.resolveUserInput(hiddenInput);
            if (!targetInput) {
                return;
            }

            this.userInputMap.set(hiddenInput, targetInput);
            this.hiddenInputMap.set(targetInput, hiddenInput);

            const data = this.loadJsonField(hiddenInput.value);
            if (this.settings.editing) {
                hiddenInput.value = JSON.stringify(data);
            }

            this.translationCache.set(hiddenInput, data);

            this.langSelectors.forEach((selector) => {
                const lang = selector.id;
                if (lang && typeof data[lang] === 'undefined') {
                    data[lang] = '';
                }
                if (lang === this.locale) {
                    this.loadLang(hiddenInput, lang);
                }
            });
        });
    }

    resolveUserInput(hiddenInput) {
        if (!hiddenInput || !(hiddenInput instanceof Element)) {
            return null;
        }

        if (this.userInputMap.has(hiddenInput)) {
            return this.userInputMap.get(hiddenInput);
        }

        if (this.settings.editing) {
            return findNextElement(hiddenInput, '.form-control');
        }

        return findNextElement(hiddenInput);
    }

    loadJsonField(value) {
        let parsed = {};
        if (value && this.isJsonValid(value)) {
            parsed = JSON.parse(value);
        }

        this.langSelectors.forEach((selector) => {
            const lang = selector.id;
            if (!lang) {
                return;
            }
            parsed[lang] = parsed[lang] || '';
        });

        return parsed;
    }

    isJsonValid(value) {
        try {
            JSON.parse(value);
            return true;
        } catch (error) {
            return false;
        }
    }

    returnLocale() {
        const activeSelector = this.langSelectors.find((selector) => {
            return selector.checked || (selector.parentElement && selector.parentElement.classList.contains('active'));
        });

        if (activeSelector && activeSelector.id) {
            return activeSelector.id;
        }

        return this.langSelectors[0] ? this.langSelectors[0].id : '';
    }

    selectLanguage(event) {
        const lang = event && event.target ? event.target.id : null;
        if (!lang) {
            return;
        }

        if (this.settings.editing) {
            this.transInputs.forEach((hiddenInput) => this.updateInputCache(hiddenInput));
        }

        this.transInputs.forEach((hiddenInput) => this.loadLang(hiddenInput, lang));
        this.locale = lang;
        this.updateLanguageLabels();
    }

    updateLanguageLabels() {
        if (typeof document === 'undefined') {
            return;
        }

        toArray(document.querySelectorAll('.js-language-label')).forEach((label) => {
            label.textContent = this.locale;
        });
    }

    prepareData() {
        this.transInputs.forEach((hiddenInput) => this.updateInputCache(hiddenInput));
    }

    updateInputCache(hiddenInput) {
        const userInput = this.userInputMap.get(hiddenInput);
        if (!hiddenInput || !userInput) {
            return;
        }

        let value = this.getUserInputValue(userInput);

        const data = this.translationCache.get(hiddenInput) || {};
        this.langSelectors.forEach((selector) => {
            const lang = selector.id;
            if (!lang) {
                return;
            }
            data[lang] = this.locale === lang ? value : (data[lang] || '');
        });

        hiddenInput.value = JSON.stringify(data);
        this.translationCache.set(hiddenInput, data);
    }

    getUserInputValue(userInput) {
        let value = '';

        if (!this.settings.editing) {
            return userInput.textContent || '';
        }

        if (userInput.classList.contains('richTextBox') && typeof window !== 'undefined' && window.tinymce) {
            const instance = window.tinymce.get(`richtext${userInput.name}`);
            if (instance && typeof instance.getContent === 'function') {
                value = instance.getContent();
                return value;
            }
        }

        if (userInput.classList.contains('easymde')) {
            const codeMirror = findCodeMirrorInstance(userInput);
            if (codeMirror) {
                value = codeMirror.getDoc().getValue();
                codeMirror.save();
                return value;
            }
        }

        if ('value' in userInput) {
            return userInput.value;
        }

        return userInput.textContent || '';
    }

    loadLang(hiddenInput, lang) {
        const userInput = this.userInputMap.get(hiddenInput);
        if (!userInput) {
            return;
        }

        const data = this.translationCache.get(hiddenInput) || {};
        const value = data[lang] || '';

        if (!this.settings.editing) {
            userInput.textContent = value;
            return;
        }

        if (userInput.classList.contains('richTextBox') && typeof window !== 'undefined' && window.tinymce) {
            const instance = window.tinymce.get(`richtext${userInput.name}`);
            if (instance && instance.initialized) {
                instance.setContent(value || '');
                return;
            }
        }

        if ('value' in userInput) {
            userInput.value = value || '';
        } else {
            userInput.textContent = value || '';
        }

        if (userInput.classList.contains('easymde')) {
            const codeMirror = findCodeMirrorInstance(userInput);
            if (codeMirror) {
                codeMirror.getDoc().setValue(userInput.value || '');
            }
        }
    }
}

const initMultilingualInstance = (element, options) => {
    if (instanceRegistry.has(element)) {
        return instanceRegistry.get(element);
    }

    const instance = new VoyagerMultilingual(element, options);
    instanceRegistry.set(element, instance);
    return instance;
};

export const initMultilingual = (target, options = {}) => {
    const elements = normalizeTargets(target);
    if (!elements.length) {
        return null;
    }

    const instances = elements.map((element) => initMultilingualInstance(element, options));
    return instances.length === 1 ? instances[0] : instances;
};

if (typeof window !== 'undefined') {
    window.VoyagerInitMultilingual = initMultilingual;
}

export { VoyagerMultilingual };
