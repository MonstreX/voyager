export const getSafeEventTarget = (event) => {
    if (!event) {
        return null;
    }
    if (event.target instanceof Element) {
        return event.target;
    }
    return event.target && event.target.parentElement ? event.target.parentElement : null;
};

export const getTargetSelector = (trigger) => {
    if (!trigger) {
        return null;
    }
    const rawSelector = trigger.getAttribute('data-target') || trigger.getAttribute('href');
    if (!rawSelector) {
        return null;
    }
    if (rawSelector.startsWith('#') || rawSelector.startsWith('.')) {
        return rawSelector;
    }
    if (rawSelector.indexOf('#') >= 0) {
        return `#${rawSelector.split('#').pop()}`;
    }
    return rawSelector;
};

export const findTargetElement = (trigger) => {
    const selector = getTargetSelector(trigger);
    if (!selector) {
        return null;
    }
    try {
        if (selector.startsWith('#')) {
            const candidate = document.getElementById(selector.slice(1));
            if (candidate) {
                return candidate;
            }
        }
        return document.querySelector(selector);
    } catch (error) {
        return null;
    }
};

export const dispatchCustomEvent = (element, name) => {
    if (!element) {
        return;
    }
    const event = new CustomEvent(name, { bubbles: true });
    element.dispatchEvent(event);
};

export const isCollapseOpen = (element) => {
    return element.classList.contains('in') || element.classList.contains('show');
};

export const collapseTransitionDuration = 350;

export const runTransition = (element, callback) => {
    let called = false;
    const handler = (event) => {
        if (event && event.target !== element) {
            return;
        }
        called = true;
        element.removeEventListener('transitionend', handler);
        callback();
    };
    element.addEventListener('transitionend', handler);
    setTimeout(() => {
        if (!called) {
            handler({ target: element });
        }
    }, collapseTransitionDuration + 50);
};

export const showCollapseElement = (element) => {
    if (!element || element.classList.contains('collapsing') || element.classList.contains('in')) {
        return;
    }
    element.classList.remove('collapse');
    element.style.display = 'block';
    const height = element.scrollHeight;
    element.style.height = '0px';
    element.offsetHeight; // force reflow
    element.classList.add('collapsing');
    element.style.transition = `height ${collapseTransitionDuration}ms ease`;
    requestAnimationFrame(() => {
        element.style.height = `${height}px`;
    });
    runTransition(element, () => {
        element.classList.remove('collapsing');
        element.classList.add('collapse', 'in', 'show');
        element.style.height = 'auto';
        element.style.transition = '';
        dispatchCustomEvent(element, 'shown.bs.collapse');
    });
};

export const hideCollapseElement = (element) => {
    if (!element || element.classList.contains('collapsing') || !element.classList.contains('in')) {
        return;
    }
    element.style.height = `${element.scrollHeight}px`;
    element.offsetHeight;
    element.classList.add('collapsing');
    element.classList.remove('collapse', 'in', 'show');
    element.style.transition = `height ${collapseTransitionDuration}ms ease`;
    requestAnimationFrame(() => {
        element.style.height = '0px';
    });
    runTransition(element, () => {
        element.classList.remove('collapsing');
        element.classList.add('collapse');
        element.style.display = 'none';
        element.style.height = '';
        element.style.transition = '';
        dispatchCustomEvent(element, 'hidden.bs.collapse');
    });
};

export const toggleCollapseElement = (element) => {
    if (!element) {
        return;
    }
    if (isCollapseOpen(element)) {
        hideCollapseElement(element);
    } else {
        showCollapseElement(element);
    }
};
