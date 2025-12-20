import { getCsrfToken } from '../modules/csrf';

export const postJson = (url, payload = {}, options = {}) => {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {})
    };

    return fetch(url, {
        method: 'POST',
        headers,
        credentials: options.credentials || 'same-origin',
        body: JSON.stringify(payload)
    });
};

export const postFormUrlEncoded = (url, params, options = {}) => {
    const body = params instanceof URLSearchParams ? params : new URLSearchParams(params || {});

    const headers = {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {})
    };

    return fetch(url, {
        method: 'POST',
        headers,
        credentials: options.credentials || 'same-origin',
        body: body.toString()
    });
};

