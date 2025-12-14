const getBootstrapCompat = () => window.VoyagerBootstrapCompat || (window.Voyager && window.Voyager.bootstrap);

export function showConfirmModal(modal) {
    const compat = getBootstrapCompat();
    if (!modal) return;
    if (compat && typeof compat.showModal === 'function') {
        compat.showModal(modal);
    } else if (modal.showModal) {
        modal.showModal();
    }
}

export function hideConfirmModal(modal) {
    const compat = getBootstrapCompat();
    if (!modal) return;
    if (compat && typeof compat.hideModal === 'function') {
        compat.hideModal(modal);
    } else if (modal.close) {
        modal.close();
    }
}

export function attachConfirmDelegates() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-confirm-target]');
        if (!trigger) return;

        const modalId = trigger.getAttribute('data-confirm-target');
        const modal = document.querySelector(modalId);
        if (!modal) return;

        const acceptButton = modal.querySelector('[data-voyager-confirm-accept]');
        const payload = trigger.dataset || {};

        // Sync name into modal body if placeholder present
        const nameSpan = modal.querySelector('.confirm_delete_name');
        if (nameSpan && payload.confirmName) {
            nameSpan.textContent = payload.confirmName;
        }

        const cleanup = () => {
            acceptButton.removeAttribute('data-confirm-url');
            acceptButton.removeAttribute('data-confirm-method');
            acceptButton.removeAttribute('data-confirm-field');
            acceptButton.removeAttribute('data-confirm-value');
            acceptButton.removeEventListener('click', acceptHandler);
        };

        const acceptHandler = () => {
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
            cleanup();
        };

        acceptButton.addEventListener('click', acceptHandler);
        showConfirmModal(modal);
    });
}
