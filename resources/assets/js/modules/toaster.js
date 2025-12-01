const DEFAULT_OPTIONS = {
    positionClass: 'toast-top-right',
    timeOut: 5000,
    closeButton: true,
    newestOnTop: true
};

const TOAST_TYPES = ['success', 'info', 'warning', 'error'];

export default class VoyagerToaster {
    constructor(options = {}) {
        this.options = { ...DEFAULT_OPTIONS, ...options };
        this.container = null;
        this.toasts = new Set();
    }

    setOptions(options = {}) {
        this.options = { ...this.options, ...options };
        if (this.container && options.positionClass) {
            this.container.className = options.positionClass;
        }
    }

    ensureContainer() {
        if (this.container || typeof document === 'undefined') {
            return;
        }
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = this.options.positionClass;
        document.body.appendChild(container);
        this.container = container;
    }

    buildToast(type, message, title, options) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        if (options.closeButton) {
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'toast-close-button';
            close.innerHTML = '&times;';
            close.addEventListener('click', (event) => {
                event.stopPropagation();
                this.removeToast(toast);
            });
            toast.appendChild(close);
        }

        if (title) {
            const titleEl = document.createElement('div');
            titleEl.className = 'toast-title';
            titleEl.innerHTML = title;
            toast.appendChild(titleEl);
        }

        const messageEl = document.createElement('div');
        messageEl.className = 'toast-message';
        messageEl.innerHTML = message || '';
        toast.appendChild(messageEl);

        toast.addEventListener('click', () => this.removeToast(toast));
        return toast;
    }

    removeToast(toast) {
        if (!toast || !toast.parentNode) {
            return;
        }
        const timer = toast.dataset.toastTimer;
        if (timer) {
            window.clearTimeout(Number(timer));
        }
        this.toasts.delete(toast);
        toast.parentNode.removeChild(toast);
        if (this.container && !this.container.childElementCount) {
            this.container.remove();
            this.container = null;
        }
    }

    show(type, message = '', title = '', overrideOptions = {}) {
        if (typeof document === 'undefined' || TOAST_TYPES.indexOf(type) === -1) {
            return null;
        }
        this.ensureContainer();
        if (!this.container) {
            return null;
        }
        const options = { ...this.options, ...overrideOptions };
        const toast = this.buildToast(type, message, title, options);
        if (options.newestOnTop) {
            this.container.prepend(toast);
        } else {
            this.container.appendChild(toast);
        }
        if (options.timeOut && options.timeOut > 0) {
            const timer = window.setTimeout(() => this.removeToast(toast), options.timeOut);
            toast.dataset.toastTimer = String(timer);
        }
        this.toasts.add(toast);
        return toast;
    }

    clear() {
        Array.from(this.toasts).forEach((toast) => this.removeToast(toast));
    }

    success(message, title, options) {
        return this.show('success', message, title, options);
    }

    info(message, title, options) {
        return this.show('info', message, title, options);
    }

    warning(message, title, options) {
        return this.show('warning', message, title, options);
    }

    error(message, title, options) {
        return this.show('error', message, title, options);
    }
}
