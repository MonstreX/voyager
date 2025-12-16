// Store toggle instances for cleanup
const voyagerToggleInstances = new WeakMap();

const resolveToggleElements = (target) => {
    if (!target || target === document) {
        return Array.from(document.querySelectorAll('.toggleswitch, input[data-toggle^="toggle"]'));
    }

    if (typeof target === 'string') {
        return Array.from(document.querySelectorAll(target));
    }

    if (target instanceof Element) {
        const directMatch = target.matches('.toggleswitch, input[data-toggle^="toggle"]') ? [target] : [];
        return directMatch.concat(
            Array.from(target.querySelectorAll('.toggleswitch, input[data-toggle^="toggle"]'))
        );
    }

    if (target instanceof NodeList || Array.isArray(target)) {
        const elements = [];
        Array.from(target).forEach((node) => {
            if (!node) {
                return;
            }
            elements.push(...resolveToggleElements(node));
        });
        return elements;
    }

    return [];
};

const getToggleOption = (input, attr, fallback) => {
    const value = input.getAttribute(attr);
    return value !== null && value !== undefined && value !== '' ? value : fallback;
};

const getToggleSizeClass = (size) => {
    switch (size) {
        case 'large':
            return 'btn-lg';
        case 'small':
            return 'btn-sm';
        case 'mini':
        case 'xs':
            return 'btn-xs';
        default:
            return '';
    }
};

const getDefaultToggleHeight = (size) => {
    switch (size) {
        case 'large':
            return 45;
        case 'small':
            return 30;
        case 'mini':
        case 'xs':
            return 22;
        default:
            return 34;
    }
};

const getDefaultToggleMinWidth = (size) => {
    switch (size) {
        case 'large':
            return 79;
        case 'small':
            return 50;
        case 'mini':
        case 'xs':
            return 35;
        default:
            return 59;
    }
};

const measureElementWidth = (tagName, className, html) => {
    const el = document.createElement(tagName);
    el.className = className;
    el.innerHTML = html;
    el.style.position = 'absolute';
    el.style.left = '-10000px';
    el.style.top = '-10000px';
    el.style.visibility = 'hidden';
    el.style.whiteSpace = 'nowrap';
    el.style.display = 'inline-block';
    document.body.appendChild(el);
    const width = el.getBoundingClientRect().width;
    document.body.removeChild(el);
    return width;
};

const buildToggleMarkup = (input, options) => {
    const onClass = `btn-${options.onstyle}`;
    const offClass = `btn-${options.offstyle}`;
    const sizeClass = getToggleSizeClass(options.size);
    const wrapper = document.createElement('div');
    wrapper.className = `toggle btn ${input.checked ? onClass : `${offClass} off`}`.trim();
    wrapper.dataset.toggle = 'toggle';
    if (sizeClass) {
        wrapper.classList.add(sizeClass);
    }
    if (options.style) {
        options.style.split(' ').filter(Boolean).forEach((cls) => wrapper.classList.add(cls));
    }
    wrapper.setAttribute('role', 'switch');

    const group = document.createElement('div');
    group.className = 'toggle-group';

    const toggleOn = document.createElement('label');
    toggleOn.className = ['btn', 'toggle-on', onClass, sizeClass].filter(Boolean).join(' ');
    toggleOn.innerHTML = options.on;

    const toggleOff = document.createElement('label');
    toggleOff.className = ['btn', 'toggle-off', offClass, sizeClass].filter(Boolean).join(' ');
    toggleOff.innerHTML = options.off;

    const handle = document.createElement('span');
    handle.className = ['toggle-handle', 'btn', 'btn-default', sizeClass].filter(Boolean).join(' ');

    group.append(toggleOn, toggleOff, handle);
    wrapper.appendChild(group);

    return {
        wrapper,
        group,
        toggleOn,
        toggleOff,
        handle,
        onClass,
        offClass
    };
};

export const initToggleSwitches = (target) => {
    const elements = resolveToggleElements(target);
    const seen = new Set();

    elements.forEach((input) => {
        if (!(input instanceof HTMLInputElement) || input.type !== 'checkbox') {
            return;
        }
        if (input.dataset.voyagerToggleInitialized === 'true') {
            return;
        }
        if (seen.has(input)) {
            return;
        }
        seen.add(input);

        const options = {
            on: getToggleOption(input, 'data-on', 'On'),
            off: getToggleOption(input, 'data-off', 'Off'),
            onstyle: getToggleOption(input, 'data-onstyle', 'primary'),
            offstyle: getToggleOption(input, 'data-offstyle', 'default'),
            size: getToggleOption(input, 'data-size', 'normal'),
            style: getToggleOption(input, 'data-style', ''),
            width: getToggleOption(input, 'data-width', null),
            height: getToggleOption(input, 'data-height', null)
        };

        const {
            wrapper,
            toggleOn,
            toggleOff,
            handle,
            onClass,
            offClass
        } = buildToggleMarkup(input, options);

        const parent = input.parentNode;
        if (!parent) {
            return;
        }
        parent.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.style.display = 'none';
        input.dataset.voyagerToggleInitialized = 'true';

        const updateState = () => {
            const checked = input.checked;
            if (checked) {
                wrapper.classList.add(onClass);
                wrapper.classList.remove(offClass, 'off');
                toggleOn.classList.add('active');
                toggleOff.classList.remove('active');
            } else {
                wrapper.classList.remove(onClass);
                wrapper.classList.add(offClass, 'off');
                toggleOn.classList.remove('active');
                toggleOff.classList.add('active');
            }
            if (input.disabled) {
                wrapper.classList.add('disabled');
                wrapper.setAttribute('aria-disabled', 'true');
                wrapper.tabIndex = -1;
            } else {
                wrapper.classList.remove('disabled');
                wrapper.removeAttribute('aria-disabled');
                wrapper.tabIndex = 0;
            }
            wrapper.setAttribute('aria-checked', checked ? 'true' : 'false');
        };

        const setChecked = () => {
            if (input.disabled) {
                return;
            }
            input.checked = !input.checked;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const adjustDimensions = () => {
            const desiredHeight = options.height
                ? (/^\d+$/.test(String(options.height)) ? Number(options.height) : null)
                : null;

            const height = desiredHeight || getDefaultToggleHeight(options.size);
            wrapper.style.height = `${height}px`;

            if (options.width) {
                wrapper.style.width = /^\d+$/.test(String(options.width))
                    ? `${options.width}px`
                    : options.width;
            } else {
                const sizeClass = getToggleSizeClass(options.size);
                const onButtonClass = ['btn', onClass, sizeClass].filter(Boolean).join(' ');
                const offButtonClass = ['btn', offClass, sizeClass].filter(Boolean).join(' ');
                const handleClass = ['btn', 'btn-default', sizeClass].filter(Boolean).join(' ');

                const onWidth = measureElementWidth('span', onButtonClass, options.on);
                const offWidth = measureElementWidth('span', offButtonClass, options.off);
                const handleWidth = measureElementWidth('span', handleClass, '&nbsp;');

                const minWidth = getDefaultToggleMinWidth(options.size);
                const calculated = Math.ceil(Math.max(onWidth, offWidth) + handleWidth);
                const width = Math.max(minWidth, calculated);

                // Safety guard against bad measurements.
                wrapper.style.width = `${Math.min(width, 600)}px`;
            }
        };

        // Store handlers for cleanup
        const handlers = {
            wrapperClick: (event) => {
                if (event.target === input) {
                    return;
                }
                event.preventDefault();
                setChecked();
            },
            wrapperKeydown: (event) => {
                if (event.key === ' ' || event.key === 'Enter') {
                    event.preventDefault();
                    setChecked();
                }
            },
            inputChange: () => updateState()
        };

        wrapper.addEventListener('click', handlers.wrapperClick);
        wrapper.addEventListener('keydown', handlers.wrapperKeydown);
        input.addEventListener('change', handlers.inputChange);

        const observer = new MutationObserver((mutations) => {
            const shouldUpdate = mutations.some((mutation) => mutation.attributeName === 'disabled');
            if (shouldUpdate) {
                updateState();
            }
        });
        observer.observe(input, { attributes: true, attributeFilter: ['disabled'] });

        // Store instance data for cleanup
        voyagerToggleInstances.set(input, {
            wrapper,
            handlers,
            observer,
            parent
        });

        requestAnimationFrame(adjustDimensions);
        updateState();
    });
};

/**
 * Destroy a toggle switch and restore the original input
 * @param {HTMLInputElement|string} target - Input element or selector
 */
export const destroyToggleSwitch = (target) => {
    const elements = resolveToggleElements(target);

    elements.forEach((input) => {
        if (!(input instanceof HTMLInputElement) || input.type !== 'checkbox') {
            return;
        }

        const instance = voyagerToggleInstances.get(input);
        if (!instance) {
            return; // Not initialized
        }

        const { wrapper, handlers, observer, parent } = instance;

        // Disconnect observer
        if (observer) {
            observer.disconnect();
        }

        // Remove event listeners
        if (handlers) {
            wrapper.removeEventListener('click', handlers.wrapperClick);
            wrapper.removeEventListener('keydown', handlers.wrapperKeydown);
            input.removeEventListener('change', handlers.inputChange);
        }

        // Restore original input
        input.style.display = '';
        if (parent && wrapper.parentNode === parent) {
            parent.insertBefore(input, wrapper);
            parent.removeChild(wrapper);
        } else if (wrapper.parentNode) {
            wrapper.parentNode.insertBefore(input, wrapper);
            wrapper.parentNode.removeChild(wrapper);
        }

        // Clear initialization flag
        delete input.dataset.voyagerToggleInitialized;

        // Remove from instances map
        voyagerToggleInstances.delete(input);
    });
};

/**
 * Refresh (destroy + reinitialize) a toggle switch
 * @param {HTMLInputElement|string} target - Input element or selector
 */
export const refreshToggleSwitch = (target) => {
    destroyToggleSwitch(target);
    initToggleSwitches(target);
};

/**
 * Subscribe to dom:updated event for automatic reinitialization
 * @param {Object} voyagerEvents - Event bus instance
 */
export const subscribeToEvents = (voyagerEvents) => {
    voyagerEvents.on('dom:updated', (container) => {
        initToggleSwitches(container);
    });
};
