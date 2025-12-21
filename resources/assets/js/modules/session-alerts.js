import { getToastr } from '../core/toastr';

const parseJsonScript = (id) => {
    const el = document.getElementById(id);
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[Voyager] Failed to parse session alerts config', error);
        return null;
    }
};

export const initSessionAlerts = () => {
    if (typeof document === 'undefined') return;
    const data = parseJsonScript('voyager-session-alerts');
    if (!data) return;

    const helpers = window.Voyager && window.Voyager.helpers;
    const toastr = getToastr();

    if (data.alerts && helpers && typeof helpers.displayAlerts === 'function') {
        helpers.displayAlerts(data.alerts, toastr);
    }

    if (data.message) {
        const type = data.alertType || 'info';
        const alerter = toastr && toastr[type];
        if (typeof alerter === 'function') {
            alerter(data.message);
        } else if (toastr && typeof toastr.error === 'function') {
            toastr.error('toastr alert-type ' + type + ' is unknown');
        }
    }
};
