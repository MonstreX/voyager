const DEFAULT_OPTIONS = {
    separator: '-',
    input: null,
    forceUpdate: false
};

const ADDITIONAL_CHAR_MAP = {
    // Russian / Ukrainian / Belarusian
    а: 'a', б: 'b', в: 'v', г: 'g', д: 'd', е: 'e', ё: 'yo', ж: 'zh', з: 'z',
    и: 'i', й: 'j', к: 'k', л: 'l', м: 'm', н: 'n', о: 'o', п: 'p', р: 'r',
    с: 's', т: 't', у: 'u', ф: 'f', х: 'h', ц: 'c', ч: 'ch', ш: 'sh',
    щ: 'sh', ъ: '', ы: 'y', ь: '', э: 'e', ю: 'yu', я: 'ya',
    ї: 'yi', і: 'i', є: 'ye', ґ: 'g',
    А: 'A', Б: 'B', В: 'V', Г: 'G', Д: 'D', Е: 'E', Ё: 'Yo', Ж: 'Zh', З: 'Z',
    И: 'I', Й: 'J', К: 'K', Л: 'L', М: 'M', Н: 'N', О: 'O', П: 'P', Р: 'R',
    С: 'S', Т: 'T', У: 'U', Ф: 'F', Х: 'H', Ц: 'C', Ч: 'Ch', Ш: 'Sh',
    Щ: 'Sh', Ъ: '', Ы: 'Y', Ь: '', Э: 'E', Ю: 'Yu', Я: 'Ya',
    Ї: 'Yi', І: 'I', Є: 'Ye', Ґ: 'G'
};

const slugifyInstances = new WeakMap();

const slugifyValue = (value, separator = '-') => {
    const sep = separator || '-';
    const escapedSep = sep.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const transliterated = (value || '')
        .toString()
        .split('')
        .map((char) => (ADDITIONAL_CHAR_MAP[char] !== undefined ? ADDITIONAL_CHAR_MAP[char] : char))
        .join('')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    const pattern = new RegExp(`\\${escapedSep}+`, 'g');
    const boundary = new RegExp(`^\\${escapedSep}+|\\${escapedSep}+$`, 'g');

    return transliterated
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
