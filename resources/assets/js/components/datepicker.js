import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

// Store flatpickr instances for cleanup
const voyagerDatepickerInstances = new WeakMap();

const getDefaultFlatpickrConfig = (type) => {
    switch (type) {
        case 'datetime':
            return {
                enableTime: true,
                altInput: true,
                altFormat: 'F j, Y H:i',
                dateFormat: 'Y-m-d\TH:i'
            };
        case 'time':
            return {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true
            };
        case 'date':
        default:
            return {
                altInput: true,
                altFormat: 'F j, Y',
                dateFormat: 'Y-m-d'
            };
    }
};

const parseFlatpickrOptions = (element) => {
    const attr = element.getAttribute('data-flatpickr') || element.getAttribute('data-datepicker');
    if (!attr) {
        return {};
    }

    try {
        const parsed = JSON.parse(attr);
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch (error) {
        console.warn('Voyager datepicker options JSON parse failed', error, attr);
        return {};
    }
};

export const initDatePickers = (scope = document) => {
    if (typeof document === 'undefined') {
        return;
    }

    const selector = 'input[data-flatpickr], input[data-flatpickr-type], input[data-datepicker]';
    const container = scope === document ? document : (scope instanceof Element ? scope : document);
    container.querySelectorAll(selector).forEach((input) => {
        if (input.dataset.flatpickrInitialized === 'true') {
            return;
        }

        const userConfig = parseFlatpickrOptions(input);
        const typeAttr = input.getAttribute('data-flatpickr-type') || input.getAttribute('data-datepicker-type');
        const guessedType = typeAttr || (userConfig.enableTime ? (userConfig.noCalendar ? 'time' : 'datetime') : 'date');
        const defaults = getDefaultFlatpickrConfig(guessedType);
        const config = {
            ...defaults,
            ...userConfig
        };

        if (typeof config.allowInput === 'undefined') {
            config.allowInput = true;
        }
        if (typeof config.disableMobile === 'undefined') {
            config.disableMobile = true;
        }

        try {
            const instance = flatpickr(input, config);
            input.dataset.flatpickrInitialized = 'true';

            // Store instance for cleanup
            voyagerDatepickerInstances.set(input, instance);
        } catch (error) {
            console.error('Voyager flatpickr init failed', error);
        }
    });
};

/**
 * Destroy a datepicker and restore the original input
 * @param {HTMLInputElement|string} target - Input element or selector
 */
export const destroyDatePicker = (target) => {
    if (typeof document === 'undefined') {
        return;
    }

    let inputs = [];
    if (typeof target === 'string') {
        inputs = Array.from(document.querySelectorAll(target));
    } else if (target instanceof HTMLInputElement) {
        inputs = [target];
    } else if (target instanceof NodeList || Array.isArray(target)) {
        inputs = Array.from(target);
    }

    inputs.forEach((input) => {
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const instance = voyagerDatepickerInstances.get(input);
        if (!instance) {
            return; // Not initialized
        }

        // Destroy flatpickr instance
        try {
            instance.destroy();
        } catch (error) {
            console.error('Voyager flatpickr destroy failed', error);
        }

        // Clear initialization flag
        delete input.dataset.flatpickrInitialized;

        // Remove from instances map
        voyagerDatepickerInstances.delete(input);
    });
};

/**
 * Refresh (destroy + reinitialize) a datepicker
 * @param {HTMLInputElement|string} target - Input element or selector
 */
export const refreshDatePicker = (target) => {
    destroyDatePicker(target);

    // Re-initialize
    if (typeof target === 'string') {
        initDatePickers(document);
    } else if (target instanceof HTMLInputElement) {
        const selector = 'input[data-flatpickr], input[data-flatpickr-type], input[data-datepicker]';
        if (target.matches(selector)) {
            const wrapper = document.createElement('div');
            wrapper.appendChild(target.cloneNode(true));
            initDatePickers(wrapper);
            // The actual re-init happens on the original input due to querySelector scope
            initDatePickers(target.parentElement || document);
        }
    } else {
        initDatePickers(document);
    }
};

/**
 * Subscribe to dom:updated event for automatic reinitialization
 * @param {Object} voyagerEvents - Event bus instance
 */
export const subscribeToEvents = (voyagerEvents) => {
    voyagerEvents.on('dom:updated', (container) => {
        initDatePickers(container);
    });
};
