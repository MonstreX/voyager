export function getToastr() {
    return window.toastr || (window.Voyager && window.Voyager.toastr) || null;
}

export function toast(type, message, title) {
    const toastr = getToastr();
    const handler = toastr && typeof toastr[type] === 'function' ? toastr[type] : null;
    if (!handler) {
        return;
    }

    if (typeof title === 'undefined') {
        handler.call(toastr, message);
        return;
    }

    handler.call(toastr, message, title);
}

