const DEFAULT_OPTIONS = {
    separator: '-',
    input: null,
    forceUpdate: false
};

const slugifyInstances = new WeakMap();

const slugifyValue = (value, separator = '-') => {
    const sep = separator || '-';
    const escapedSep = sep.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const normalized = (value || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    const pattern = new RegExp(`\\${escapedSep}+`, 'g');
    const boundary = new RegExp(`^\\${escapedSep}+|\\${escapedSep}+$`, 'g');

    return normalized
        .replace(/[^a-z0-9]/g, sep)
        .replace(pattern, sep)
        .replace(boundary, '');
};

export class VoyagerSlugify {
    constructor(element, options = {}) {
        this.element = element;
        this.options = { ...DEFAULT_OPTIONS, ...options };
        this.forceUpdate =
            this.options.forceUpdate || element.dataset.slugForceupdate === 'true';
        this.shouldUpdate = element.value === '';
        this.handleChange = this.onChange.bind(this);
        this.origin = this.resolveOrigin();
        this.attach();
    }

    resolveOrigin() {
        if (this.options.input instanceof HTMLInputElement) {
            return this.options.input;
        }
        if (typeof this.options.input === 'string') {
            return document.querySelector(this.options.input);
        }

        const originName = this.element.getAttribute('data-slug-origin');
        if (!originName) {
            return null;
        }

        const form = this.element.closest('form') || document;
        return form.querySelector(`input[name=\"${originName}\"]`);
    }

    attach() {
        if (!this.origin) {
            return;
        }
        this.origin.addEventListener('keyup', this.handleChange);
        this.origin.addEventListener('change', this.handleChange);
    }

    refresh() {
        this.shouldUpdate = this.element.value === '';
    }

    onChange(event) {
        const code = event.keyCode || event.which;
        if (code > 34 && code < 41) {
            return;
        }
        if (!this.origin) {
            return;
        }
        const currentValue = this.element.value;
        if (this.shouldUpdate || currentValue === '' || this.forceUpdate) {
            this.element.value = slugifyValue(
                this.origin.value || '',
                this.options.separator
            );
            this.shouldUpdate = true;
        }
    }
}

export const initSlugifyField = (target, options = {}) => {
    const element =
        typeof target === 'string'
            ? document.querySelector(target)
            : target instanceof Element
            ? target
            : null;

    if (!element || !(element instanceof HTMLInputElement)) {
        return null;
    }

    if (slugifyInstances.has(element)) {
        const instance = slugifyInstances.get(element);
        instance.refresh();
        return instance;
    }

    const instance = new VoyagerSlugify(element, options);
    slugifyInstances.set(element, instance);
    return instance;
};

export const initSlugifyFields = (scope) => {
    let elements = [];
    if (!scope || scope === document) {
        elements = document.querySelectorAll('[data-slug-origin]');
    } else if (typeof scope === 'string') {
        elements = document.querySelectorAll(scope);
    } else if (scope instanceof HTMLInputElement) {
        elements = [scope];
    } else if (scope instanceof Element) {
        elements = scope.querySelectorAll('[data-slug-origin]');
    } else if (scope instanceof NodeList || Array.isArray(scope)) {
        elements = Array.from(scope).filter(
            (node) => node instanceof HTMLInputElement
        );
    }

    Array.from(elements).forEach((element) => initSlugifyField(element));
};

export const slugifyString = (value, separator = '-') =>
    slugifyValue(value, separator);
