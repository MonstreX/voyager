const getBootstrapCompat = () => window.VoyagerBootstrapCompat || (window.Voyager && window.Voyager.bootstrap);

const activeHandlers = new WeakMap();

export function showConfirmModal(modal) {
    const compat = getBootstrapCompat();
    if (!modal) return;
    if (compat && typeof compat.showModal === 'function') {
        compat.showModal(modal);
    } else {
        // Minimal fallback for plain DOM
        modal.classList.add('in');
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade in';
        backdrop.dataset.modalTarget = modal.id || '';
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
    }
}

export function hideConfirmModal(modal) {
    const compat = getBootstrapCompat();
    if (!modal) return;
    if (compat && typeof compat.hideModal === 'function') {
        compat.hideModal(modal);
    } else {
        modal.classList.remove('in');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        const backdrop = modal.id ? document.querySelector(`.modal-backdrop[data-modal-target="${modal.id}"]`) : null;
        if (backdrop) {
            backdrop.remove();
        }
        if (!document.querySelector('.modal.in')) {
            document.body.classList.remove('modal-open');
        }
    }
}

const cleanupModalHandler = (modal) => {
    if (!modal) return;
    const active = activeHandlers.get(modal);
    if (!active) return;

    const { acceptButton, acceptHandler } = active;
    if (acceptButton && acceptHandler) {
        acceptButton.removeEventListener('click', acceptHandler);
    }

    if (acceptButton) {
        acceptButton.removeAttribute('data-confirm-url');
        acceptButton.removeAttribute('data-confirm-method');
        acceptButton.removeAttribute('data-confirm-field');
        acceptButton.removeAttribute('data-confirm-value');
        acceptButton.removeAttribute('data-confirm-form');
        acceptButton.removeAttribute('data-confirm-form-action');
    }

    activeHandlers.delete(modal);
};

const resolveCallback = (path) => {
    if (!path) return null;
    const trimmed = String(path).trim();
    if (!trimmed) return null;
    const parts = trimmed.split('.').map((part) => part.trim()).filter(Boolean);
    let current = window;
    for (const part of parts) {
        if (current && Object.prototype.hasOwnProperty.call(current, part)) {
            current = current[part];
        } else if (current && part in current) {
            current = current[part];
        } else {
            return null;
        }
    }
    return typeof current === 'function' ? current : null;
};

export function attachConfirmDelegates() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-confirm-target]');
        if (!trigger) return;

        event.preventDefault();

        const modalId = trigger.getAttribute('data-confirm-target');
        const modal = document.querySelector(modalId);
        if (!modal) return;

        const acceptButton = modal.querySelector('[data-voyager-confirm-accept]');
        if (!acceptButton) return;
        const payload = trigger.dataset || {};

        cleanupModalHandler(modal);

        if (!modal.dataset.voyagerConfirmCleanupInit) {
            modal.dataset.voyagerConfirmCleanupInit = '1';

            modal.addEventListener('click', (e) => {
                if (e.target.closest('[data-dismiss="modal"]')) {
                    cleanupModalHandler(modal);
                }
            });

            modal.addEventListener('hidden.bs.modal', () => cleanupModalHandler(modal));
        }

        // Sync name into modal body if placeholder present
        const nameSpan = modal.querySelector('.confirm_delete_name');
        if (nameSpan && payload.confirmName) {
            nameSpan.textContent = payload.confirmName;
        }

        const acceptHandler = () => {
            if (payload.confirmCallback) {
                const callback = resolveCallback(payload.confirmCallback);
                if (callback) {
                    const result = callback({ trigger, modal, payload });
                    Promise.resolve(result)
                        .then((shouldClose) => {
                            if (shouldClose === false) {
                                return;
                            }
                            hideConfirmModal(modal);
                        })
                        .catch((error) => {
                            console.error('[Voyager] confirm callback failed', error);
                            hideConfirmModal(modal);
                        })
                        .finally(() => cleanupModalHandler(modal));
                    return;
                }
            }

            if (payload.confirmForm) {
                const form = document.querySelector(payload.confirmForm);
                if (form) {
                    if (payload.confirmFormAction) {
                        form.setAttribute('action', payload.confirmFormAction);
                    }
                    cleanupModalHandler(modal);
                    form.submit();
                    return;
                }
            }

            if (payload.confirmHref) {
                const href = payload.confirmHref;
                cleanupModalHandler(modal);
                hideConfirmModal(modal);
                if (href) {
                    window.location.href = href;
                }
                return;
            }

            if (payload.confirmUrl) {
                const method = payload.confirmMethod || 'POST';
                const headers = { 'X-CSRF-TOKEN': payload.csrf || '' };
                fetch(payload.confirmUrl, { method, headers })
                    .finally(() => hideConfirmModal(modal));
            } else if (payload.confirmField) {
                const input = document.querySelector(`[name="${payload.confirmField}"]`);
                if (input) {
                    input.value = payload.confirmValue || '1';
                }
                hideConfirmModal(modal);
            } else {
                hideConfirmModal(modal);
            }
            cleanupModalHandler(modal);
        };

        acceptButton.addEventListener('click', acceptHandler);
        activeHandlers.set(modal, { acceptButton, acceptHandler });
        showConfirmModal(modal);
    });
}

export function confirmAction(modal, { confirmName } = {}) {
    if (!modal) {
        return Promise.resolve(false);
    }

    const acceptButton = modal.querySelector('[data-voyager-confirm-accept]');
    if (!acceptButton) {
        return Promise.resolve(false);
    }

    cleanupModalHandler(modal);

    const nameSpan = modal.querySelector('.confirm_delete_name');
    if (nameSpan && confirmName) {
        nameSpan.textContent = confirmName;
    }

    let accepted = false;
    let finished = false;

    return new Promise((resolve) => {
        const finish = (result) => {
            if (finished) return;
            finished = true;
            resolve(result);
        };

        const hiddenHandler = () => {
            modal.removeEventListener('hidden.bs.modal', hiddenHandler);
            cleanupModalHandler(modal);
            finish(accepted);
        };

        modal.addEventListener('hidden.bs.modal', hiddenHandler);

        const acceptHandler = () => {
            accepted = true;
            hideConfirmModal(modal);
        };

        acceptButton.addEventListener('click', acceptHandler);
        activeHandlers.set(modal, { acceptButton, acceptHandler });

        showConfirmModal(modal);
    });
}
