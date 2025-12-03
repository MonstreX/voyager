import {
    getSafeEventTarget,
    findTargetElement,
    dispatchCustomEvent,
    isCollapseOpen,
    showCollapseElement,
    hideCollapseElement,
    toggleCollapseElement
} from '../helpers/dom';

const modalStack = [];
const modalBackdropMap = new Map();

const showModalElement = (modal) => {
    if (!modal || modal.classList.contains('voyager-modal-visible')) {
        return;
    }
    modal.classList.add('voyager-modal-visible');
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('in', 'show');
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade in';
    backdrop.dataset.voyagerModalId = modal.id || '';
    document.body.appendChild(backdrop);
    modalBackdropMap.set(modal, backdrop);
    modalStack.push(modal);
    document.body.classList.add('modal-open');
    dispatchCustomEvent(modal, 'shown.bs.modal');
};

const hideModalElement = (modal) => {
    if (!modal || !modal.classList.contains('voyager-modal-visible')) {
        return;
    }
    if (modal.contains(document.activeElement) && document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
    }
    modal.classList.remove('voyager-modal-visible');
    modal.classList.remove('in', 'show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    const backdrop = modalBackdropMap.get(modal);
    if (backdrop && backdrop.parentNode) {
        backdrop.parentNode.removeChild(backdrop);
    }
    modalBackdropMap.delete(modal);
    const index = modalStack.indexOf(modal);
    if (index !== -1) {
        modalStack.splice(index, 1);
    }
    if (modalStack.length === 0) {
        document.body.classList.remove('modal-open');
    }
    dispatchCustomEvent(modal, 'hidden.bs.modal');
};

const currentModal = () => {
    return modalStack.length ? modalStack[modalStack.length - 1] : null;
};

const dropdownState = {
    openDropdown: null
};

const closeDropdownMenu = (dropdown) => {
    if (!dropdown) {
        return;
    }
    dropdown.classList.remove('open');
    if (dropdown === dropdownState.openDropdown) {
        dropdownState.openDropdown = null;
    }
};

const toggleDropdownFromTrigger = (trigger) => {
    const dropdown = trigger.closest('.dropdown');
    if (!dropdown) {
        return;
    }
    if (dropdown === dropdownState.openDropdown) {
        closeDropdownMenu(dropdown);
        return;
    }
    if (dropdownState.openDropdown) {
        closeDropdownMenu(dropdownState.openDropdown);
    }
    dropdown.classList.add('open');
    dropdownState.openDropdown = dropdown;
};

const activateTabTrigger = (trigger) => {
    if (!trigger) {
        return;
    }
    const target = findTargetElement(trigger);
    if (!target) {
        return;
    }
    const parentNav = trigger.closest('ul');
    if (parentNav) {
        parentNav.querySelectorAll('.active').forEach((active) => active.classList.remove('active'));
    }
    const li = trigger.closest('li');
    if (li) {
        li.classList.add('active');
    }
    const container = target.parentElement;
    if (container) {
        container.querySelectorAll('.tab-pane').forEach((pane) => {
            pane.classList.remove('active', 'in');
            pane.style.display = 'none';
        });
    }
    target.classList.add('active', 'in');
    target.style.display = 'block';
    dispatchCustomEvent(target, 'shown.bs.tab');
};

let tooltipElement = null;
const tooltipState = {
    activeTrigger: null
};

const ensureTooltipElement = () => {
    if (!tooltipElement) {
        tooltipElement = document.createElement('div');
        tooltipElement.className = 'voyager-tooltip';
        document.body.appendChild(tooltipElement);
    }
    return tooltipElement;
};

const positionTooltip = (trigger) => {
    const tooltip = ensureTooltipElement();
    const text = trigger.getAttribute('title') || trigger.getAttribute('data-original-title');
    if (!text) {
        return;
    }
    tooltip.textContent = text;
    const rect = trigger.getBoundingClientRect();
    const top = window.scrollY + rect.top - 8;
    const left = window.scrollX + rect.left + rect.width / 2;
    tooltip.style.top = `${top}px`;
    tooltip.style.left = `${left}px`;
    tooltip.classList.add('visible');
    tooltipState.activeTrigger = trigger;
};

const hideTooltip = () => {
    if (tooltipElement) {
        tooltipElement.classList.remove('visible');
    }
    tooltipState.activeTrigger = null;
};

const initTooltips = (scope) => {
    let elements = [];
    if (scope) {
        if (Array.isArray(scope)) {
            elements = scope;
        } else if (typeof NodeList !== 'undefined' && scope instanceof NodeList) {
            elements = Array.from(scope);
        } else {
            elements = [scope];
        }
    } else {
        elements = Array.from(document.querySelectorAll('[data-toggle="tooltip"]'));
    }

    elements.forEach((element) => {
        if (element && element instanceof Element && !element.getAttribute('title') && element.getAttribute('data-original-title')) {
            element.setAttribute('title', element.getAttribute('data-original-title'));
        }
    });
};

const initBootstrapCompat = () => {
    document.addEventListener('click', (event) => {
        const baseTarget = getSafeEventTarget(event);
        const modalTrigger = baseTarget && baseTarget.closest('[data-toggle="modal"]');
        if (modalTrigger) {
            event.preventDefault();
            const modal = findTargetElement(modalTrigger);
            if (modal) {
                showModalElement(modal);
            }
            return;
        }

        const dismissTrigger = baseTarget && baseTarget.closest('[data-dismiss="modal"]');
        if (dismissTrigger) {
            event.preventDefault();
            const modal = dismissTrigger.closest('.modal');
            if (modal) {
                hideModalElement(modal);
            }
            return;
        }

        const dropdownTrigger = baseTarget && baseTarget.closest('[data-toggle="dropdown"], .dropdown-toggle');
        if (dropdownTrigger) {
            event.preventDefault();
            toggleDropdownFromTrigger(dropdownTrigger);
            return;
        }

        const collapseTrigger = baseTarget && baseTarget.closest('[data-toggle="collapse"]');
        if (collapseTrigger) {
            event.preventDefault();
            const target = findTargetElement(collapseTrigger);
            if (target) {
                const parentSelector = collapseTrigger.getAttribute('data-parent');
                if (parentSelector) {
                    const parent = document.querySelector(parentSelector);
                    if (parent) {
                        parent.querySelectorAll('.collapse.show, .collapse.in').forEach((el) => {
                            if (el !== target) {
                                hideCollapseElement(el);
                            }
                        });
                    }
                }
                const isExpanded = !isCollapseOpen(target);
                toggleCollapseElement(target);
                collapseTrigger.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            }
            return;
        }

        const tabTrigger = baseTarget && baseTarget.closest('[data-toggle="tab"]');
        if (tabTrigger) {
            event.preventDefault();
            activateTabTrigger(tabTrigger);
            return;
        }

        const buttonToggle = baseTarget && baseTarget.closest('[data-toggle="buttons"] label');
        if (buttonToggle) {
            const input = buttonToggle.querySelector('input');
            const group = buttonToggle.closest('[data-toggle="buttons"]');
            if (input && group) {
                if (input.type === 'radio') {
                    group.querySelectorAll('label').forEach((label) => label.classList.remove('active'));
                    buttonToggle.classList.add('active');
                    input.checked = true;
                } else {
                    buttonToggle.classList.toggle('active');
                    input.checked = buttonToggle.classList.contains('active');
                }
            }
        }
    });

    document.addEventListener('click', (event) => {
        const modal = currentModal();
        const baseTarget = getSafeEventTarget(event);
        if (modal && baseTarget === modal) {
            hideModalElement(modal);
        }
        if (dropdownState.openDropdown && (!baseTarget || !baseTarget.closest('.dropdown'))) {
            closeDropdownMenu(dropdownState.openDropdown);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const modal = currentModal();
            if (modal) {
                hideModalElement(modal);
                return;
            }
            if (dropdownState.openDropdown) {
                closeDropdownMenu(dropdownState.openDropdown);
            }
        }
    });

    document.addEventListener('mouseenter', (event) => {
        const baseTarget = getSafeEventTarget(event);
        const trigger = baseTarget && baseTarget.closest('[data-toggle="tooltip"]');
        if (!trigger) {
            return;
        }
        positionTooltip(trigger);
    }, true);

    document.addEventListener('mouseleave', (event) => {
        const baseTarget = getSafeEventTarget(event);
        const trigger = baseTarget && baseTarget.closest('[data-toggle="tooltip"]');
        if (trigger) {
            hideTooltip();
        }
    }, true);

    initTooltips();
    document.querySelectorAll('.tab-pane').forEach((pane) => {
        if (pane.classList.contains('active')) {
            pane.style.display = 'block';
        } else {
            pane.style.display = 'none';
        }
    });
};

export {
    initBootstrapCompat,
    showModalElement as showModal,
    hideModalElement as hideModal,
    initTooltips
};
