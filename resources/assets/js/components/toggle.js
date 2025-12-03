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
            if (options.width) {
                wrapper.style.width = /^\d+$/.test(String(options.width))
                    ? `${options.width}px`
                    : options.width;
            } else {
                const onWidth = toggleOn.getBoundingClientRect().width;
                const offWidth = toggleOff.getBoundingClientRect().width;
                const handleWidth = handle.getBoundingClientRect().width || 0;
                const width = Math.max(onWidth, offWidth) + handleWidth / 2;
                wrapper.style.width = `${Math.ceil(width)}px`;
            }

            if (options.height) {
                wrapper.style.height = /^\d+$/.test(String(options.height))
                    ? `${options.height}px`
                    : options.height;
                toggleOn.style.lineHeight = toggleOn.offsetHeight + 'px';
                toggleOff.style.lineHeight = toggleOff.offsetHeight + 'px';
            } else {
                const height = Math.max(toggleOn.offsetHeight, toggleOff.offsetHeight);
                wrapper.style.height = `${height}px`;
            }
        };

        wrapper.addEventListener('click', (event) => {
            if (event.target === input) {
                return;
            }
            event.preventDefault();
            setChecked();
        });
        wrapper.addEventListener('keydown', (event) => {
            if (event.key === ' ' || event.key === 'Enter') {
                event.preventDefault();
                setChecked();
            }
        });
        input.addEventListener('change', () => updateState());

        const observer = new MutationObserver((mutations) => {
            const shouldUpdate = mutations.some((mutation) => mutation.attributeName === 'disabled');
            if (shouldUpdate) {
                updateState();
            }
        });
        observer.observe(input, { attributes: true, attributeFilter: ['disabled'] });

        requestAnimationFrame(adjustDimensions);
        updateState();
    });
};
