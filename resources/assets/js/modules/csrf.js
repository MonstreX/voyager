const getMetaContent = (name) => {
    if (typeof document === 'undefined') {
        return '';
    }

    const meta = document.querySelector(`meta[name="${name}"]`);
    return meta ? meta.getAttribute('content') || '' : '';
};

export const getCsrfToken = () => getMetaContent('csrf-token');

export const applyjQueryCsrfHeader = () => {
    if (typeof window === 'undefined' || !window.jQuery) {
        return;
    }

    const token = getCsrfToken();
    if (!token) {
        return;
    }

    window.jQuery.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': token,
        },
    });
};

applyjQueryCsrfHeader();
