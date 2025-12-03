import PerfectScrollbar from 'perfect-scrollbar';
import { getSafeEventTarget, findTargetElement, toggleCollapseElement, hideCollapseElement, isCollapseOpen } from '../helpers/dom';

export const initGlobalEvents = () => {
    const appContainer = document.querySelector('.app-container');
    const hamburgerButtons = document.querySelectorAll('.hamburger, .navbar-expand-toggle');

    const sideMenuEl = document.querySelector('.side-menu');
    if (sideMenuEl) {
        new PerfectScrollbar(sideMenuEl);
    }

    // Loader handling
    const loader = document.getElementById('voyager-loader');
    if (loader) {
        loader.style.display = 'none';
    }

    hamburgerButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (appContainer) {
                appContainer.classList.toggle('expanded');
            }
            const isActive = button.classList.toggle('is-active');
            if (isActive) {
                window.localStorage.setItem('voyager.stickySidebar', true);
            } else {
                window.localStorage.setItem('voyager.stickySidebar', false);
            }
        });
    });

    const sideMenuNav = document.querySelector('.side-menu .nav');
    if (sideMenuNav) {
        sideMenuNav.addEventListener('click', (event) => {
            const baseTarget = getSafeEventTarget(event);
            const trigger = baseTarget && baseTarget.closest('.dropdown [data-toggle="collapse"]');
            if (!trigger) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            const activeDropdown = trigger.closest('.dropdown');
            if (!activeDropdown) {
                return;
            }
            sideMenuNav.querySelectorAll('.dropdown .collapse').forEach((section) => {
                if (section.closest('.dropdown') !== activeDropdown) {
                    hideCollapseElement(section);
                }
            });
            const target = findTargetElement(trigger);
            if (target) {
                const isExpanded = !isCollapseOpen(target);
                toggleCollapseElement(target);
                trigger.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            }
        });
    }

    document.addEventListener('click', (event) => {
        const collapseTrigger = event.target.closest('.panel-heading a.panel-action[data-toggle="panel-collapse"]');
        if (collapseTrigger) {
            event.preventDefault();
            const panel = collapseTrigger.closest('.panel');
            const body = panel ? panel.querySelector('.panel-body') : null;
            if (!panel || !body) {
                return;
            }
            const isCollapsed = collapseTrigger.classList.contains('panel-collapsed');
            if (!isCollapsed) {
                body.style.display = 'none';
                collapseTrigger.classList.add('panel-collapsed');
                collapseTrigger.classList.remove('voyager-angle-up');
                collapseTrigger.classList.add('voyager-angle-down');
            } else {
                body.style.display = '';
                collapseTrigger.classList.remove('panel-collapsed');
                collapseTrigger.classList.remove('voyager-angle-down');
                collapseTrigger.classList.add('voyager-angle-up');
            }
            return;
        }

        const fullscreenTrigger = event.target.closest('.panel-heading a.panel-action[data-toggle="panel-fullscreen"]');
        if (fullscreenTrigger) {
            event.preventDefault();
            fullscreenTrigger.classList.toggle('voyager-resize-full');
            fullscreenTrigger.classList.toggle('voyager-resize-small');
            const panel = fullscreenTrigger.closest('.panel');
            if (panel) {
                panel.classList.toggle('is-fullscreen');
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.keyCode === 83) {
            const saveButtons = document.querySelectorAll('.btn.save');
            saveButtons.forEach((button) => button.click());
            event.preventDefault();
        }
    });
};
