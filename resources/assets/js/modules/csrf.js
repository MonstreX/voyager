const getMetaContent = (name) => {
    if (typeof document === 'undefined') {
        return '';
    }

    const meta = document.querySelector(`meta[name="${name}"]`);
    return meta ? meta.getAttribute('content') || '' : '';
};

export const getCsrfToken = () => getMetaContent('csrf-token');
